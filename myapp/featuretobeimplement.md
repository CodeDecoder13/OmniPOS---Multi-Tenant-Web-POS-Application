# Modern POS System - Features To Be Implemented

## Context
Full audit of OmniPOS revealed 13 missing features that modern POS systems need. The system has strong CRUD and reporting but lacks: refund flow, hold/park orders, split payments, per-item notes, low stock alerts, digital receipts, and all automation (zero background jobs, zero scheduled tasks, zero POS event listeners). This plan implements everything in Tier 1 (core POS gaps) + Tier 2 (automation & background processing).

## Implementation Order

**Phase 1:** 1.6 Per-Item Notes → 1.7 Customer History → 1.1 Refund Flow → 1.2 Hold/Park Orders
**Phase 2:** 1.3 Split Payments → 1.4 Low Stock Alerts → 1.5 Digital Receipts
**Phase 3:** 2.1 Event Foundation → 2.4 Auto-Close Shifts → 2.3 EOD Summary → 2.2 Low Stock Email → 2.5 Queued Receipts → 2.6 Auto Reorder

---

## PHASE 1: Core POS Gaps

### 1.6 Per-Item Notes (Small)

Allow cashiers to add notes per order item ("no onions", "extra spicy"), displayed in KDS/KOT/receipt.

**Migration:** `database/migrations/tenant/2025_01_01_000051_add_notes_to_order_items_table.php`
- Add `notes` varchar(500) nullable to `order_items`

**Modify:**
- `app/Models/Tenant/OrderItem.php` - add `notes` to `$fillable`
- `app/Http/Requests/Tenant/CheckoutRequest.php` - add `items.*.notes` rule
- `app/Services/Tenant/PosService.php` - pass `$item['notes']` in checkout loop
- `resources/js/types/models.ts` - add `notes?: string | null` to `OrderItem`
- `resources/js/pages/tenant/pos/Index.vue` - add note input per cart item
- `resources/js/pages/tenant/orders/Show.vue` - display item notes
- `resources/views/receipts/thermal.blade.php` - show notes under each item
- `resources/views/kitchen/kot.blade.php` - show notes under each item
- `resources/js/pages/tenant/kitchen/Index.vue` - display item notes in KDS cards

---

### 1.7 Customer Purchase History (Small)

Customer detail page showing order history, total spend, avg order value, last visit.

**New files:**
- `resources/js/pages/tenant/customers/Show.vue` - stats cards + paginated order table

**Modify:**
- `app/Http/Controllers/Tenant/CustomerController.php` - add `show()` method
- `app/Services/Tenant/CustomerService.php` - add `getStats()` and `getOrderHistory()`
- `routes/tenant.php` - add `GET /{tenant}/customers/{customer}`
- `resources/js/types/models.ts` - add `CustomerStats` interface
- `resources/js/pages/tenant/customers/Index.vue` - make customer name a Link to show page

**Stats computed from Completed orders:** total_orders, total_spent, avg_order_value, last_order_date

---

### 1.1 Refund Flow (Medium)

Full + partial refunds on completed orders with item selection, inventory restoration, refund payment records.

**Migrations:**
- `2025_01_01_000052_create_refunds_table.php` - id, tenant_id, order_id, refund_number, type(full/partial), amount, reason, created_by, timestamps
- `2025_01_01_000053_create_refund_items_table.php` - id, refund_id, order_item_id, quantity, amount
- `2025_01_01_000054_add_refund_tracking_to_payments_and_orders.php` - orders.refunded_amount, payments.refund_id

**New files:**
- `app/Models/Tenant/Refund.php` - belongsTo Order, hasMany RefundItem, hasOne Payment
- `app/Models/Tenant/RefundItem.php` - belongsTo Refund, belongsTo OrderItem
- `app/Services/Tenant/RefundService.php` - `processRefund()`: create Refund + RefundItems, Payment record, update order.refunded_amount, restore inventory, set status to Refunded if fully refunded
- `app/Http/Requests/Tenant/RefundRequest.php` - validate type, reason, items array for partial
- `resources/js/pages/tenant/orders/Refund.vue` - full/partial toggle, item selection, reason, amount preview

**Modify:**
- `app/Enums/AdjustmentType.php` - add `Refund` case
- `app/Models/Tenant/Order.php` - add refunded_amount to fillable/casts, add `refunds()` relationship, add `canBeRefunded()` helper
- `app/Models/Tenant/Payment.php` - add refund_id to fillable, add `refund()` relationship
- `app/Http/Controllers/Tenant/OrderController.php` - add `refundPage()` and `refund()` methods
- `app/Services/Tenant/InventoryService.php` - add `incrementForRefund()` (mirrors incrementForVoid but for selected items)
- `routes/tenant.php` - add `GET/POST /{tenant}/orders/{order}/refund`
- `resources/js/types/models.ts` - add Refund, RefundItem interfaces; add refunded_amount + refunds to Order
- `resources/js/pages/tenant/orders/Show.vue` - add "Refund" button, show refund details section

**Refund number format:** `REF-YYYYMMDD-XXXX`
**Partial refund amount:** item unit price x refunded quantity per item
**Full refund amount:** order.total - order.refunded_amount

---

### 1.2 Hold/Park Orders (Medium)

Save in-progress cart without checkout, list held orders, recall to resume.

**Migration:** `2025_01_01_000055_add_held_at_to_orders_table.php` - orders.held_at timestamp nullable

**New files:**
- `app/Services/Tenant/HeldOrderService.php` - `hold()` (create Pending order with items, no payment/inventory), `listHeld()`, `recall()`, `delete()`
- `app/Http/Requests/Tenant/HoldOrderRequest.php` - like CheckoutRequest but no payment fields

**Modify:**
- `app/Http/Controllers/Tenant/PosController.php` - add `holdOrder()`, `heldOrders()`, `recallOrder()`, `deleteHeldOrder()`
- `app/Models/Tenant/Order.php` - add held_at to fillable/casts
- `app/Services/Tenant/PosService.php` - if checkout receives `order_id`, complete existing held order (delete old items, recreate, then normal checkout flow)
- `app/Http/Requests/Tenant/CheckoutRequest.php` - add optional `order_id` field
- `routes/tenant.php` - add `POST pos/hold`, `GET pos/held-orders`, `GET pos/held-orders/{order}`, `DELETE pos/held-orders/{order}`
- `resources/js/types/models.ts` - add `held_at` to Order
- `resources/js/pages/tenant/pos/Index.vue` - "Hold Order" button, held orders drawer with count badge, recall populates cart, track `recalledOrderId`

**Key:** No inventory decrement on hold, no payment created. Only on final checkout.

---

## PHASE 2: Payments & Notifications

### 1.3 Split Payments (Medium)

Accept multiple payment methods per order (e.g., part cash + part card).

**No DB changes** - `payments` table already supports multiple rows per order.

**Modify:**
- `app/Http/Requests/Tenant/CheckoutRequest.php` - replace single payment fields with `payments[]` array: `payments.*.method`, `payments.*.amount`, `payments.*.amount_tendered`, `payments.*.reference_number`. Validate sum >= total.
- `app/Services/Tenant/PosService.php` - replace single payment creation with loop over `$data['payments']`
- `resources/js/pages/tenant/pos/Index.vue` - split payment modal: multiple rows with method/amount, "Add Payment" button, remaining balance display. Single payment is default (backward compatible)
- `resources/views/receipts/thermal.blade.php` - iterate `$order->payments` instead of single payment
- `app/Services/Tenant/ReceiptService.php` - pass full payments collection to view

**Change calculation:** Only on last cash payment. ShiftService already aggregates by method, no changes needed there.

---

### 1.4 Low Stock Alerts In-App (Small-Medium)

Check threshold after decrement, create database notification, show in notification bell.

**Migration:** `2025_01_01_000056_create_notifications_table.php` - Laravel standard notifications table

**New files:**
- `app/Notifications/LowStockNotification.php` - database channel, includes product_name, branch_name, current_stock, threshold, message
- `app/Http/Controllers/Tenant/NotificationController.php` - `index()` (JSON list + unread count), `markAsRead()`, `markOneAsRead()`
- `resources/js/components/NotificationBell.vue` - bell icon with unread badge, dropdown list, mark-read, polls every 30s

**Modify:**
- `app/Services/Tenant/InventoryService.php` - after `decrementForSale()`, check if stock CROSSED threshold (was above, now at/below) and send `LowStockNotification` to branch managers + tenant owners
- `routes/tenant.php` - add `GET notifications`, `POST notifications/mark-read`, `POST notifications/{notification}/read`
- Tenant layout Vue - add `<NotificationBell />` to header
- `resources/js/types/models.ts` - add `AppNotification` interface

**Trigger:** Only fires when stock crosses threshold downward (prevents repeat notifications).

---

### 1.5 Digital Receipts (Small-Medium)

Email receipt to customer + generate shareable public link.

**Migration:** `2025_01_01_000057_add_receipt_token_to_orders_table.php` - orders.receipt_token varchar(64) nullable unique

**New files:**
- `app/Mail/ReceiptMail.php` - Mailable with order summary + "View Online" button
- `resources/views/emails/receipt.blade.php` - HTML email template
- `app/Http/Controllers/ReceiptController.php` - public `show()` by token (no auth required)
- `resources/js/pages/public/Receipt.vue` - public receipt page, minimal layout

**Modify:**
- `app/Models/Tenant/Order.php` - add receipt_token to fillable
- `app/Services/Tenant/PosService.php` - generate `Str::random(64)` token on checkout
- `app/Services/Tenant/ReceiptService.php` - add `emailReceipt()` and `getShareableUrl()`
- `app/Http/Controllers/Tenant/OrderController.php` - add `emailReceipt()` and `receiptLink()` methods
- `routes/tenant.php` - add `POST orders/{order}/receipt/email`, `GET orders/{order}/receipt/link`
- `routes/web.php` - add `GET /receipts/{token}` public route
- `resources/js/pages/tenant/orders/Show.vue` - "Email Receipt" + "Copy Link" buttons
- `resources/js/pages/tenant/pos/Index.vue` - after checkout success, show email/link options
- `resources/js/types/models.ts` - add `receipt_token` to Order

---

## PHASE 3: Automation & Events

### 2.1 Event-Driven Foundation (Medium)

Create Laravel events for all key POS actions. All listeners initially synchronous.

**New event files in `app/Events/`:**
- `OrderCompleted.php` (Order, Tenant)
- `OrderVoided.php` (Order, Tenant, voidReason)
- `OrderRefunded.php` (Refund, Order, Tenant)
- `OrderHeld.php` (Order, Tenant)
- `LowStockReached.php` (Inventory, Tenant)
- `ShiftOpened.php` (Shift)
- `ShiftClosed.php` (Shift)
- `PaymentReceived.php` (Payment, Order)

**New listener files in `app/Listeners/`:**
- `SendLowStockDatabaseNotification.php` - moves inline notification from InventoryService
- `LogOrderActivity.php` - logs to ActivityLog

**Modify:**
- `app/Services/Tenant/PosService.php` - dispatch `OrderCompleted` after checkout
- `app/Services/Tenant/OrderService.php` - dispatch `OrderVoided` after void
- `app/Services/Tenant/RefundService.php` - dispatch `OrderRefunded` after refund
- `app/Services/Tenant/HeldOrderService.php` - dispatch `OrderHeld` after hold
- `app/Services/Tenant/InventoryService.php` - dispatch `LowStockReached` instead of inline notification; also check `reorder_point` crossing
- `app/Services/Tenant/ShiftService.php` - dispatch `ShiftOpened`/`ShiftClosed`
- `app/Providers/AppServiceProvider.php` - register all event-listener mappings

---

### 2.4 Auto-Close Stale Shifts (Small)

Scheduled command closes shifts open >12 hours.

**New:** `app/Console/Commands/CloseStaleShifts.php`
- Signature: `shifts:close-stale {--hours=12}`
- Queries open shifts older than cutoff, calls `ShiftService::closeShift()` with ending_cash=0 and auto-close note

**Modify:** `routes/console.php` - `Schedule::command('shifts:close-stale')->hourly()`

---

### 2.3 End-of-Day Summary (Medium)

Daily email to tenant owners with revenue, orders, voids, refunds summary.

**New files:**
- `app/Console/Commands/SendEndOfDaySummary.php` - iterates active tenants, builds daily stats, notifies owner
- `app/Notifications/EndOfDaySummaryNotification.php` - mail channel with date, order_count, total_revenue, avg_order_value, voided_count, refunded_count

**Modify:** `routes/console.php` - `Schedule::command('reports:daily-summary')->dailyAt('23:30')`

Skips tenants with zero orders that day.

---

### 2.2 Low Stock Email Notifications (Small)

Email branch managers when stock hits threshold. Queued, with 24h debounce.

**New files:**
- `app/Listeners/SendLowStockEmailNotification.php` - implements ShouldQueue, listens to `LowStockReached`, emails branch managers + tenant owner
- `app/Notifications/LowStockEmailNotification.php` - mail channel with product name, branch, stock level, link to inventory

**Modify:** `app/Providers/AppServiceProvider.php` - register listener on `LowStockReached`

**Debounce:** Cache key `low_stock_email_{inventory_id}` with 24h TTL to prevent spam.

---

### 2.5 Queued Receipt Email (Small)

Move email receipt to background job.

**New:** `app/Jobs/SendReceiptEmail.php` - implements ShouldQueue, wraps ReceiptMail dispatch, 3 retries

**Modify:** `app/Services/Tenant/ReceiptService.php` - `emailReceipt()` dispatches `SendReceiptEmail` job instead of inline `Mail::send()`

Requires queue worker (`php artisan queue:work`). Falls back to sync if `QUEUE_CONNECTION=sync`.

---

### 2.6 Auto Stock Reorder (Medium)

Auto-create draft PO when stock hits reorder point.

**Migration:** `2025_01_01_000058_add_reorder_fields_to_inventory_table.php` - add `reorder_point` int nullable, `reorder_quantity` int nullable to `inventory`

**New:** `app/Listeners/CreateAutoReorderPurchaseOrder.php` - implements ShouldQueue, listens to `LowStockReached`
- Checks reorder_point and reorder_quantity are configured
- Checks no existing open PO for same product+branch
- Finds preferred supplier from `product_supplier.is_preferred`
- Creates Draft PO via existing `PurchaseOrderService::create()`

**Modify:**
- `app/Models/Tenant/Inventory.php` - add reorder_point, reorder_quantity to fillable
- `app/Providers/AppServiceProvider.php` - register listener
- `app/Services/Tenant/InventoryService.php` - fire `LowStockReached` on reorder_point crossing too
- `resources/js/types/models.ts` - add reorder_point, reorder_quantity to Inventory
- Inventory management UI - add reorder fields

---

## New Files Summary (40 total)

| Phase | Count | Key files |
|-------|-------|-----------|
| Phase 1 | 13 | 5 migrations, 2 models, 2 services, 2 requests, 2 Vue pages |
| Phase 2 | 9 | 2 migrations, 1 notification, 1 controller, 1 mailable, 1 blade, 3 Vue components/pages |
| Phase 3 | 18 | 1 migration, 8 events, 4 listeners, 2 commands, 1 job, 2 notifications |

## Critical Files Modified Across Features

- `app/Services/Tenant/PosService.php` - touched by 1.6, 1.2, 1.3, 1.5, 2.1
- `app/Services/Tenant/InventoryService.php` - touched by 1.1, 1.4, 2.1, 2.6
- `resources/js/pages/tenant/pos/Index.vue` - touched by 1.6, 1.2, 1.3, 1.5
- `resources/js/types/models.ts` - touched by all features
- `routes/tenant.php` - touched by 1.7, 1.1, 1.2, 1.4, 1.5
- `app/Http/Requests/Tenant/CheckoutRequest.php` - touched by 1.6, 1.2, 1.3

## Dependency Graph

```
1.6 (Per-Item Notes)        -- standalone
1.7 (Customer History)      -- standalone
1.1 (Refund Flow)           -- depends on 1.6 (includes notes in refund display)
1.2 (Hold/Park Orders)      -- depends on 1.6 (preserves per-item notes)
1.3 (Split Payments)        -- standalone, modifies checkout flow
1.4 (Low Stock In-App)      -- standalone, adds notifications
1.5 (Digital Receipts)      -- standalone, adds email + public link
2.1 (Event Foundation)      -- refactors 1.1, 1.2, 1.3, 1.4 to use events
2.4 (Auto-Close Shifts)     -- depends on 2.1 (dispatches ShiftClosed event)
2.3 (EOD Summary)           -- standalone scheduled command
2.2 (Low Stock Email)       -- depends on 2.1 (listens to LowStockReached)
2.5 (Queued Receipt Email)  -- depends on 1.5 (wraps email in job)
2.6 (Auto Stock Reorder)    -- depends on 2.1 (listens to LowStockReached)
```

## Future Considerations (Tier 3 & 4)

These are documented for future implementation:

**Tier 3 - Customer Engagement:**
- Loyalty points system (earn/redeem, BranchSettings.customer_loyalty flag already exists)
- Customer insights dashboard (visit frequency, avg spend, preferences)
- Birthday rewards (auto promo code generation)
- Customer groups/segments (VIP, Wholesale with group pricing)

**Tier 4 - Advanced Modern Features:**
- Real-time dashboard with WebSockets (replace KDS polling)
- Offline mode / PWA (service worker + IndexedDB queue)
- Credit/tab system (PaymentStatus::Pending already exists)
- Employee time clock (leverages existing PIN auth)
- Expense tracking (revenue - COGS - expenses = profitability)
- Multi-currency support
