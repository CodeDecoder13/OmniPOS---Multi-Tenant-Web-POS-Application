# OmniPOS Security and Backend Audit

Scope: `myapp/` (Laravel 12.70.1, Inertia/Vue). Read-only review. All paths are relative to `myapp/`.
Method: read routes, middleware, controllers, services, FormRequests, models, migrations; ran `php artisan event:list` and `php artisan route:list --json` (read-only).
Tests: `tests/` only contains Auth, Settings, Dashboard and Example tests. There are no Inventory, Category, or business-type tests, so the recent changes cannot have broken existing tests and `--filter=Inventory` would run nothing. phpunit.xml uses sqlite `:memory:` (safe), but tests were not run because there is nothing relevant to run.

Severity key: Critical = cross-tenant compromise or account takeover by any signed-up user. High = data corruption or privilege escalation inside a tenant, or cross-tenant writes needing a permission. Medium = correctness or abuse with real impact. Low = hardening.

Note: anyone can self-register and becomes the Owner of a new tenant with every permission (`IdentifyTenant.php:56-57`). So "requires permission X" never protects against cross-tenant attacks: an attacker simply registers a tenant.

---

## Critical

### C1. Any tenant owner/manager can take over ANY user account in the system (email + password change)
- Evidence: `app/Http/Controllers/Tenant/UserController.php:164-190`. For a non-owner `$userId`, the code runs `User::findOrFail($userId)` (line 182) and `$user->update(['email'=>..., 'password'=>...])` (line 190). Nothing checks that `$userId` is a member of the current tenant. The `TenantUser` update on lines 192-197 is scoped, but the `User` update is global.
- Scenario: attacker registers tenant A (gets Owner, so `users.edit-role`). `PUT /A/users/123` with `{name, email:"attacker@x.com", password:"...", role_id:<A's role>}` where 123 is the owner of tenant B. User 123's email and password are overwritten, and the attacker logs in as B's owner. User ids are sequential, so every account can be enumerated.
- Fix: at the top of `update()` (and the same for `toggleActive`/`remove`, which are already scoped but should share this guard):
  ```php
  abort_unless(TenantUser::where('tenant_id', $tenant->id)->where('user_id', $userId)->exists(), 404);
  ```
  Also stop letting a tenant manager change another user's global email/password. Users are global across tenants, so changing them should be owner-only at minimum.

### C2. IDOR on inventory adjust/history (implicit binding, no tenant check)
- Evidence: `routes/tenant.php:137,139` bind `{inventory}`. `InventoryController.php:32,62` type-hints `Inventory $inventory`. `Inventory` has no global scope or `resolveRouteBinding` (`app/Models/Tenant/Inventory.php`), and `InventoryService::adjust()` (line 72) and `getHistory()` (line 219) do not check the tenant.
- Scenario: tenant A user `POST /A/inventory/999/adjust {"type":"damage","quantity_change":-50}`, where 999 belongs to tenant B. B's stock is changed, an adjustment row is written under B's tenant_id, and thresholds can be nulled. `GET /A/inventory/999/history` leaks B's adjustment history including creator names.
- Fix: change the signature to `int $inventory` and resolve with the existing `$this->inventoryService->findForTenant($tenant, $inventory)` (`InventoryService.php:65`), which already exists but is unused.

### C3. IDOR on kitchen status update and KOT PDF
- Evidence: `routes/tenant.php:327-328`, `KitchenDisplayController.php:63,78` bind `Order $order` with no tenant or branch check. `KitchenDisplayService::updateStatus()` (line 45) and `KotService::generatePdf()` (line 12) trust the model.
- Scenario: `PATCH /A/kitchen/5000/status {"kitchen_status":"served"}` changes tenant B's order. The JSON response returns the full fresh `Order` (totals, customer_id, notes). `GET /A/kitchen/5000/kot/pdf` downloads B's order items.
- Fix: `int $order` plus `Order::forTenant($tenant)->where('branch_id', $tenantUser->branch_id)->findOrFail($order)`.

### C4. Cross-tenant inventory writes via purchase orders and stock transfers
- Evidence: `app/Http/Requests/Tenant/StockTransferRequest.php:17-21` and `PurchaseOrderRequest.php:17-22` use global `exists:branches,id`, `exists:products,id`, `exists:suppliers,id`. `StockTransferService::ship()` (lines 78-85) does `Inventory::firstOrCreate(['product_id'=>..., 'branch_id'=>$transfer->source_branch_id])` and decrements it. `PurchaseOrderService::receive()` (lines 134-141) increments.
- Scenario: attacker creates a transfer with `source_branch_id` = victim branch and `product_id` = victim product, then calls ship. The victim's stock is decremented, and with no lower bound it can go negative. A PO with the victim's branch and product, once received, inflates the victim's stock. PO and transfer show pages also leak the victim's branch, supplier and product names and SKUs through the eager loads.
- Fix: scope every `exists` rule, e.g. `Rule::exists('branches','id')->where('tenant_id', $tenant->id)` (the same pattern `CheckoutRequest.php:41` already uses). Apply it to `PurchaseOrderRequest`, `StockTransferRequest`, `ProductRequest.php:66,68` (`addon_ids`, `branch_ids`), `BranchMenuController.php:53`, `ShiftScheduleRequest.php:18` (and add a tenant-membership check for `user_id` on line 17), and `UserController.php:96-97,145,170-171` (later code partially re-checks these, but the rules should be scoped).

---

## High

### H1. Privilege escalation: a `users.edit-role` holder can grant themselves the Owner role
- Evidence: `UserController.php:166-197`. The only owner guard is for editing the owner's own row (line 136). A manager editing their own row (`$isOwnerRow` is false) may set `role_id` to the tenant's system `owner` role, and `IdentifyTenant.php:56` then grants every permission.
- Scenario: a manager with `users.edit-role` sends `PUT /A/users/{self} {role_id: <owner role id>}` and gets full permissions, including settings, roles and business-type reset (the last is owner_id-gated, but everything else opens up).
- Fix: in `update()` and `store()`, reject `$role->is_system && $role->slug === 'owner'` unless the actor is `$tenant->owner_id`. Also forbid non-owners from editing their own role. `UserImportService::import()` (line 111) also trusts client `role_id`/`branch_id` (it is owner-only, but should re-run `validateCsv` server side).

### H2. PIN enumeration oracle (unthrottled) leads to POS operator impersonation
- Evidence: `SetupController.php:64-89` (`POST {tenant}/setup/pin`) has no permission and no throttle (`routes/tenant.php:40`). It returns "This PIN is already in use by another user" when the submitted PIN matches anyone's hash. `PosPinController::set` (lines 84-97) is the same oracle with throttle 5/min, and `verify` returns the owner's name and permissions for a matching PIN.
- Scenario: a cashier scripts 10,000 POSTs to `/A/setup/pin` (4-digit space) and learns every PIN in use. They then call `pos/pin/verify` once per PIN to map PINs to names, and check out as another operator (`PosController.php:97-113`), attributing sales and cash to someone else.
- Fix: drop the uniqueness check entirely (PINs are verified by identity plus PIN, or per-user lookup), or at least return a generic error, add `throttle:5,1` to `setup/pin`, and return 404 from `setup` POSTs once a branch exists. Also consider requiring the operator to pick their user, then verify only that user's hash (this also removes the N x bcrypt CPU cost per attempt in `verify`).

### H3. Refund and void double-restock and over-refund per item
- Evidence: `RefundService.php:45-61` caps only the money total (lines 68-71). It never checks per-item already-refunded quantity, and the requested quantity is never compared with `orderItem->quantity` (`RefundRequest.php:22` only has `min:1`). A "full" refund after a partial (lines 32-41) restocks the full quantity again. `Order::canBeVoided()` (`Order.php:71-74`) allows voiding a partially refunded order, and `incrementForVoid` (`InventoryService.php:172`) restocks the full quantities again.
- Scenario: order of 1 x A (100) + 1 x B (100). Partial refund A qty 1, then again A qty 1. Both pass the money cap (200), A is restocked twice and B never comes back. Or: partial refund, then void, and items are restocked twice. With `{quantity: 50}` on one item the money cap still applies, but inventory restock is +50.
- Fix: in partial refunds compute `$alreadyRefunded = RefundItem::where('order_item_id',$id)->sum('quantity')` and reject `qty > item.quantity - alreadyRefunded`. For "full", refund only the remaining quantities. Make `canBeVoided()` also require `refunded_amount == 0`. Move `voidOrder`'s check inside the transaction with `lockForUpdate()` like `RefundService.php:22` does (it currently allows a double void race that restocks twice).

### H4. PO receive and stock transfer ship/receive have no state machine, so stock can be replayed or forged
- Evidence: `PurchaseOrderController.php:102-110` and `StockTransferController.php:64-95` call the service with raw `$request->input('received_quantities'|'sent_quantities')` (no validation). The services (`PurchaseOrderService.php:119`, `StockTransferService.php:71,110`) never check the current status.
- Scenario: POST `/receive` twice on a Received or Cancelled PO and stock is added twice. Post `received_quantities: {12: -500}` and stock is decremented below zero. Ship a transfer twice and the source is decremented twice. Receive a Pending (never shipped) transfer with `{id: 999}` and stock is created from nothing. `update()` on a Received PO rewrites its items.
- Fix: guard each transition (`abort_unless($po->status === Sent || $po->status === Partial, 422)`, `ship` only from Pending, `receive` only from InTransit, `cancel` not from Completed). Lock the row (`lockForUpdate`) inside the transaction, validate quantities as `integer|min:0` with `received <= ordered - already_received` and `sent <= on_hand`.

### H5. Every low-stock and auto-receipt listener runs twice (auto-discovery plus explicit registration)
- Evidence: `php artisan event:list` shows `LowStockReached` with `SendLowStockDatabaseNotification`, `SendLowStockEmailNotification` and `CreateAutoReorderPurchaseOrder` each registered twice, and `OrderCompleted -> SendAutoReceiptEmail` twice. Cause: `AppServiceProvider.php:42-48` calls `Event::listen` for listeners that Laravel 12 already auto-discovers from `app/Listeners`.
- Scenario: every sale that crosses a threshold sends two emails and two DB notifications, and races two auto-reorder jobs (the "existing PO" check in `CreateAutoReorderPurchaseOrder.php:33-39` is not locked, so duplicate POs are possible). Every customer gets two receipt emails.
- Fix: delete the six discovered `Event::listen` lines in `AppServiceProvider::registerEvents()`. Keep only `LogOrderActivity` if it is not discovered (it is listed once, so discovery does not pick it up). Verify afterwards with `php artisan event:list`.

### H6. Fresh deploy or new environment cannot migrate; tenant-folder migrations are never run
- Evidence: `database/migrations/tenant/*` (suppliers, promotions, tables, refunds, purchase orders, stock transfers, variations, addons, notifications) is a subdirectory. `php artisan migrate --force` (`railway.toml` preDeployCommand) only scans `database/migrations`, and nothing calls `loadMigrationsFrom` (there is no `config/tenancy.php`). Central migrations reference these tables, e.g. `2025_01_01_000046_add_table_id_to_orders_table.php:14` (FK to `tables`) and `000048` (FK to `promotions`).
- Scenario: a new Railway environment, a DB restore, or a CI Postgres run fails at migration 000046 ("relation tables does not exist"), or ends up missing tables. Production only works because those tables were created some other way.
- Fix: move the files into `database/migrations/` (keeping their timestamps), or add `$this->loadMigrationsFrom(database_path('migrations/tenant'))` in `AppServiceProvider::boot()`. Most files already use `hasTable`/`hasColumn` guards. Then remove unused `stancl/tenancy` (see L8).

### H7. Google registration lets an attacker create a verified account for any email, plus a pre-hijack via Google link
- Evidence: `CreateNewUser.php:33-79` takes `email` from form input but marks it verified because `session('google_user')` exists (lines 73-77). The Google email in the session is never compared. `GoogleController.php:35-56` links Google to any existing account by email and force-verifies it, even if that account was created unverified with an attacker-chosen password.
- Scenario 1: attacker completes Google OAuth with their own account (new user), then submits `/register` with `email=ceo@victim.com`. The account is created as verified, and the store is registered under that email.
- Scenario 2: attacker registers `victim@gmail.com` with a password (unverified). Later the victim signs in with Google, the account is linked and verified, and the attacker's password still works.
- Fix: in `CreateNewUser`, when `$isGoogleRegistration`, set `$input['email'] = $googleUser['email']` (ignore the form). In `GoogleController`, when linking to an existing unverified account, reset its password (`password => null`) or refuse to link. Also check `$googleUser->user['email_verified'] ?? false`.

---

## Medium

### M1. Checkout money integrity: negative totals and client-priced modifiers
- Evidence: `PosService.php:185-193`: a fixed `discount_amount` is not capped at the subtotal (`CheckoutRequest.php:61` is only `min:0`), so `$afterDiscount` and `total` can go negative and still pass the payment check (lines 241-251). Variation and add-on prices come from the client (`PosService.php:151-165`, `CheckoutRequest.php:46,49`) and are never looked up from `variation_options`/`addons`.
- Scenario: `discount_type=fixed, discount_amount=1000` on a 100 order stores `total=-900` (when discounts are enabled for the branch) and corrupts revenue, tax and shift cash. Sending `addon_price: 0` for a paid add-on undercharges.
- Fix: `$discountAmount = min($discountAmount, $subtotal)` and `$total = max(0, $total)`. Resolve modifier and add-on prices server side by id (`VariationOption::whereIn(...)`, `Addon::forTenant(...)`) instead of trusting the snapshot fields.

### M2. Order, refund, PO and transfer number generation races; UTC date prefixes
- Evidence: `PosService.php:359-378` and `RefundService.php:167-186` lock only the last existing row (nothing to lock for the first order of the day, and in Postgres READ COMMITTED two waiters both read the same max). `PurchaseOrderService.php:176` and `StockTransferService.php:188` have no lock at all. `orders` has `unique(tenant_id, order_number)` (`create_orders_table.php:28`), so the race surfaces as a 500 on checkout. `now()` is UTC (`config/app.php:68`), so in Manila the "day" in the number rolls over at 08:00.
- Fix: use a per-tenant counter row updated with `lockForUpdate()` (or `pg_advisory_xact_lock(hashtext(tenant_id))` at the start of the transaction) in one shared helper. Format with `now('Asia/Manila')` or the tenant's timezone.

### M3. "Today" and day-grouping use UTC
- Evidence: `config/app.php:68` `'timezone' => 'UTC'`. `DashboardController.php:53-79` (`Carbon::today()`, `whereDate`, `DATE(created_at)`), `PosController.php:196` (billing history "today"), `ReportService.php:450-451` (`startOfDay`), `date_trunc` in `ReportService.php:58` and `AIInsightsService`. The `reports:daily-summary` schedule at 23:30 UTC is 07:30 Manila the next day (`routes/console.php:12`).
- Scenario: a sale at 07:00 Manila on the 5th is reported as the 4th. The dashboard "Today" is empty until 08:00 local.
- Fix: the smallest fix is `'timezone' => env('APP_TIMEZONE', 'Asia/Manila')` (Postgres `timestamp without time zone` columns then store local time consistently). The better fix is to keep UTC storage and pass `->timezone($tz)` boundaries into queries, plus `date_trunc(..., created_at AT TIME ZONE 'Asia/Manila')`.

### M4. Shift open/close can target any user or shift in the tenant
- Evidence: `ShiftController.php:53-60` takes `user_id` from the request without checking tenant membership or PIN verification. `close()` (lines 69-80) closes any tenant shift id with a caller-supplied `ending_cash`. `openShift` has a check-then-insert race (`ShiftService.php:21-44`), and `closeShift` checks `isOpen()` outside the transaction without a lock (lines 52-56).
- Fix: require `user_id` to be the authenticated user or the session's `pos_verified_operator_id` (same rule as `PosController.php:97-113`). Restrict `close` to the shift owner or `shifts.view`/owner. Wrap the open check in a transaction with `lockForUpdate`, or add a partial unique index `(tenant_id, user_id) WHERE status='open'`.

### M5. Inventory adjust 500s when a threshold field is cleared (recent change)
- Evidence: `InventoryController.php:46-48` uses `$request->has('low_stock_threshold')`, and the rule is `nullable` (`InventoryAdjustmentRequest.php:28`), so an explicit `null` is written to `low_stock_threshold`, which is `integer NOT NULL default 0` (`create_inventory_table.php:18`). Postgres then throws a not-null violation and the whole request returns 500. This happens after `adjust()` already committed the stock change in its own transaction, so the user sees an error while stock did change.
- Fix: `if ($request->filled('low_stock_threshold'))`, or drop `nullable` from that one rule. Wrap the adjust call and the threshold update in one `DB::transaction`.
- Also in the same area: `InventoryService::scopedQuery` (`InventoryService.php:31-32`) uses `like`, which is case-sensitive on Postgres ("coke" will not find "Coke"). Use `ilike` (other services have the same issue). `stats()` counts zero-stock items with threshold 0 as "in stock" (line 45). This is cosmetic.

### M6. Product delete loses the image and then fails with an FK violation
- Evidence: `ProductService.php:130-134` deletes the image file first, then `$product->delete()`. `purchase_order_items.product_id` and `stock_transfer_items.product_id` have no cascade (`tenant/..._044...php:13`, `..._042...php:13`), so any product ever on a PO or transfer throws a 500, and the image is already gone.
- Fix: delete the row first, then the file (`DB::transaction(fn() => $product->delete()); $this->deleteImage($product);`), and return a friendly error (or deactivate) when PO or transfer items exist.

### M7. Business-type reset silently destroys PO and transfer history and orphans images
- Evidence: `CategoryService.php:39-45` hard-deletes all `purchase_order_items` and `stock_transfer_items` for the tenant's products. The PO and transfer headers keep `total_amount` but lose every line. Product image files are never removed. Held (pending) orders keep items with `product_id = NULL`, so recall and checkout then fail validation (`CheckoutRequest.php:41`).
- Fix: before the reset, block it (or warn) when POs or transfers exist, or set the item `product_id` nullable with `nullOnDelete` plus a stored `product_name` snapshot like `order_items`. Delete held orders in the same transaction. Collect `image_path`s and delete files after commit. The owner check (`SettingsController.php:38`) and the `CHANGE` confirmation are correct.

### M8. Reports break for large tenants and miscount refunds
- Evidence: `ReportService.php:80,110` do `->pluck('id')` of every completed order in range and then `whereIn('order_id', $ids)`. The date range is unbounded (`ReportController.php:24-25`). Above 65,535 orders Postgres rejects the statement (bind parameter limit), and below that it is a large memory and network hit. Partially refunded orders stay `Completed` and are summed at full `total` (lines 22-26, 132, 163), so revenue is overstated (`refunded_amount` is ignored).
- Fix: replace the pluck with a subquery, as `getSummary` already does (lines 29-38): `->whereIn('order_id', $this->baseQuery(...)->select('id'))`. Use `SUM(total - refunded_amount)` for revenue. Cap the range (e.g. `before_or_equal` 366 days after `date_from`).

### M9. Missing indexes on Postgres foreign keys used in every report and shift query
- Evidence: Laravel `foreignId()->constrained()` does not create an index on Postgres. No index exists on `order_items.order_id` (`create_order_items_table.php`), `payments.order_id` (`create_payments_table.php`), `orders.shift_id` (`add_shift_id_to_orders_table.php`), `orders.branch_id`, or `inventory_adjustments.inventory_id`.
- Fix: one migration that adds `index('order_id')` on order_items and payments, `index(['tenant_id','branch_id','status','created_at'])` on orders, `index('shift_id')` on orders, and `index('inventory_id')` on inventory_adjustments.

### M10. Promo code race at registration gives a free paid plan
- Evidence: `CreateNewUser.php:49-65` validates the promo, then `RegisterService.php:49-53` re-checks it without a lock. If the promo became invalid (max_uses reached by a concurrent signup), the tenant is still created on the paid plan with `promo_code_id = null`. `incrementUsage()` is unlocked, so `max_uses` can be overshot.
- Fix: in `RegisterService`, `PromoCode::where(...)->lockForUpdate()->first()`. If it is invalid for a paid plan, throw a `ValidationException` (rolls back the transaction) instead of continuing.

### M11. Chat send has no throttle and emails two hardcoded personal addresses per message
- Evidence: `routes/tenant.php:352` has no throttle. `ChatService.php:105` does `Mail::to(['rhuzzel.paramio@omnipos.shop', 'boyparamio@gmail.com'])->queue(...)` for every message. Attachments go to the `public` disk (`ChatService.php:76-80`), which is ephemeral on Railway, as are receipt logos (`ReceiptCustomizationController.php:59-62`).
- Fix: add `throttle:20,1` to the chat routes. Move the recipients to `config('mail.support_recipients')` / env. Store attachments on the S3 disk already used for products (`config/filesystems.php:18`), or mount a Railway volume.

### M12. Production runs on `php artisan serve`
- Evidence: `railway.toml` `startCommand = "php artisan serve ..."` is the single-threaded PHP dev server. One slow PDF or report blocks every other request for every tenant.
- Fix: use FrankenPHP/Octane or nginx + php-fpm (Railpack supports a PHP-FPM start). At minimum set `PHP_CLI_SERVER_WORKERS=4`.

### M13. Setup endpoints usable by any tenant user
- Evidence: `routes/tenant.php:38-40` have no permission. `SetupController::store` (lines 49-62) creates a branch (plan-limited) and reassigns the caller to it even when branches already exist.
- Fix: `abort_if($tenant->branches()->exists(), 403)` at the top of `store()`, or restrict it to the owner.

---

## Low

- L1. Receipt email endpoint can send to any address with no throttle: `OrderController.php:97-110` (`orders.view`). Add `throttle:10,1`. Note: `SendReceiptEmail` has `tries=3` but no `failed()`. The same applies to the shift mail listeners, which use synchronous `Mail::send` inside queued listeners (fine), and `CloseStaleShifts` closes with `ending_cash => 0` (`CloseStaleShifts.php:31-34`), producing a fake large cash shortage. Pass `null` or `expected_cash`.
- L2. Public receipt route `/receipts/{token}` has no throttle (`web.php:126-128`). The token is 64 random chars, so guessing is not practical. Add `throttle:60,1` anyway.
- L3. `TrackPageVisit` (`TrackPageVisit.php:21-26`) stores raw IP plus user agent synchronously on every public GET, including Railway's healthcheck on `/` (`railway.toml` `healthcheckPath = "/"`). Truncate or hash the IP, or point the healthcheck at `/up`.
- L4. Dashboard (`routes/tenant.php:42`) has no permission, so cashiers see tenant-wide revenue, top products and payment breakdown. Gate the financial blocks on `reports.view`.
- L5. Kitchen poll `since` (`KitchenDisplayController.php:53`) is unvalidated. A malformed value makes Postgres throw a 500. Add `'since' => 'nullable|date'`.
- L6. User import `rows.*.email` is not checked for uniqueness at import time (`UserController.php:252-259`), so concurrent or duplicate rows give a 500 from the unique index. Re-run `validateCsv` server side.
- L7. Email OTP is stored in plaintext and compared with `!==` (`VerifyEmailOtpController.php:36`). Use `hash_equals` and store a hash. Throttle 6/min is present.
- L8. `stancl/tenancy` is installed but unconfigured (no `config/tenancy.php`). Its queue bootstrapper still hooks every job (`event:list`). Remove the package or configure it.
- L9. Session cookie `secure` is env-driven (`config/session.php:172`). Make sure `SESSION_SECURE_COOKIE=true` and `APP_DEBUG=false` on Railway (`.env*` files could not be read in this review).
- L10. Dependencies: `barryvdh/laravel-dompdf: "*"` and `laravel/socialite: "*"` (`composer.json:13,17`). Locked at dompdf 3.1.6 / socialite 5.31.0 (current, `enable_remote=false`), but `*` allows a future major on `composer update`. Pin `^3.1` and `^5.31`.
- L11. `PosPinController::verify` runs `Hash::check` against every PIN holder in the tenant on every attempt (lines 25-31). That is N bcrypt operations per request, a CPU DoS lever for large tenants (fixed by H2's per-user verify).
- L12. A `roles.edit` holder can add any permission to their own role (`RoleService::update`, `RoleController.php:64-76`). This is acceptable if intended. Otherwise block editing the caller's own role.

---

## Overlaps (pick one source of truth)

1. Stock movement logic exists in 7 copies: `InventoryService::adjust` (locks), `decrementForSale` (locks), `incrementForVoid` (locks), `RefundService::incrementForRefund` (locks), `PurchaseOrderService::receive` (no lock), `StockTransferService::ship/receive/cancel` (no lock, no negative check), and `ProductService::create/update` initial stock. Source of truth: one `InventoryService::move(Tenant, productId, branchId, int $delta, AdjustmentType, ?ref, userId)` that locks, checks negativity and fires `LowStockReached`. Fixes H4 and part of C4 in one place.
2. Document number generation exists 5 times (`PosService::generateOrderNumber`, `RefundService::generateRefundNumber`, `PurchaseOrderService::generatePoNumber`, `StockTransferService::generateTransferNumber`, `HeldOrderService::generateHeldNumber`). Make one `DocumentNumber::next($tenant, 'ORD')` helper (M2).
3. Sales stats are computed in 6 places with differing rules: `DashboardController.php:53-139` (business logic in the controller), `ReportService`, `AIInsightsService`, `SendEndOfDaySummary`, `SendShiftEndedEmail`, and `ShiftService::getShiftSummary`. They disagree on refunds and day boundaries. Source of truth: `ReportService` (move the dashboard queries into it).
4. Promotion discount math is duplicated: `PosService.php:199-217` vs `PromotionService::applyPromoCode` (lines 84-91). Source of truth: a `Promotion::discountFor(float $amount)` method used by both.
5. Promo code validation for signup exists 3 times: the `web.php:77-102` closure, `CreateNewUser.php:56-65`, and `RegisterService.php:49-53`. Keep the `RegisterService` (locked) check and have the other two call it.
6. PIN uniqueness check exists 3 times: `PosPinController::set`, `PosPinController::setForUser`, and `SetupController::storePin` (and is itself the H2 oracle). Delete it, or move it into one `TenantUser::pinTaken()`.
7. Owner checks and the owner-permission derivation are duplicated: `(int) user()->id !== (int) owner_id` appears in `UserController` (x3), `PosPinController`, and `SettingsController` (strict, no cast). The "owner gets all permissions" logic is in both `IdentifyTenant.php:56-60` and `PosPinController.php:51-55`. Add `Tenant::isOwner(User)` and `Role::effectivePermissions()`.
8. Low-stock definition exists 3 times: `Inventory::scopeLowStock`, `ReportService.php:210`, and the crossing logic in `InventoryService.php:158-164`. Put it on the `Inventory` model (`isLowStock()`).
9. Event listener registration happens twice (H5). Rely on discovery only.
10. Tenant-scoped `exists` rules are written inline in some FormRequests and missing in others (C4). Add a tiny helper, e.g. `TenantRules::exists('branches')`, and use it everywhere.

No duplicate route URIs or names were found (`route:list --json`, 262 routes). Create pages plus inline/dialog endpoints (`categories/create` + `categories/inline`) both route through `CategoryService::create`, which is fine.

---

## Verified OK

- Int-id tenant routes resolve through `forTenant(...)->findOrFail()`: orders (with branch scope), customers, products, categories, branches, suppliers, addons, promotions (incl. usage), tables, roles, shifts, shift schedules, purchase orders, transfers, held orders, `users/{user}/pin`, user toggle and remove.
- `pos/customers/search`, `pos/products`, `billing-history`, notifications (`$request->user()->notifications()`), and chat (scoped by user + tenant) are correctly scoped.
- Product images: `ProductImageController` scopes by tenant, checks `basename` equality, and the filename regex blocks traversal. Uploads use `image|mimes|max` (product 2MB, logo 1MB, chat 2MB `image`).
- Public receipt: 64-char `Str::random` unique token, and the response exposes no internal ids.
- `CheckoutRequest` scopes products, customers, tables and promotions to the tenant. The checkout path is transactional, locks inventory and promotion rows, and validates payment sum >= total.
- `RefundService` locks the order row and caps by refundable balance. `RoleService` protects the owner role's permissions and blocks system role deletion.
- `changeBusinessType` is owner-only server side with a typed confirmation, transactional.
- Raw SQL: all `selectRaw`/`orderByRaw`/`DB::raw` uses are constant strings or bound parameters. `date_trunc` format comes from a `match` whitelist. LIKE wildcards in user input only widen matches inside the tenant.
- No unescaped Blade (`{!! !!}` only in vendor mail layouts). `v-html` is used only for paginator labels (server generated) and the 2FA QR SVG. dompdf `enable_remote=false`.
- Mass assignment: models use `$fillable`. Controllers pass `validated()`, and services set `tenant_id` explicitly after spreads. No `$request->all()` in `app/Http`.
- Fortify: login 5/min per email+IP, 2FA 5/min, 2FA confirm and password confirm enabled. Admin guard is separate with session regenerate on login. Google redirect/callback throttled. Mail preview routes only under `app()->isLocal()`. `APP_DEBUG` defaults to false. TrustProxies `*` is fine behind Railway.
- `pos_pin` is bcrypt-hashed and hidden from serialization. Checkout requires session proof of PIN for a different operator (8h).
- Plan limits are enforced in `BranchService`, `ProductService`, `UserController::store`, and `UserImportService::import` (with count). There is no product CSV import to bypass.
- Queue: database driver, and jobs dispatched inside transactions roll back with them (same connection). Queued mail and notifications are used for Welcome, OTP, LoginAlert, LowStock and EndOfDay.
- `routes/web.php` public pages only expose the active plans' public columns, and `getPublicStats` is cached for 300s.

---

## Top 10 fixes (ranked)

1. C1: tenant-membership check in `UserController::update` (and stop editing other users' global email/password). One `abort_unless` line.
2. C2 + C3: replace implicit `Inventory`/`Order` binding with `forTenant()->findOrFail()` in `InventoryController` and `KitchenDisplayController`.
3. C4: tenant-scope every `exists:` rule (PO, transfer, product addon/branch ids, branch menu, schedules, users).
4. H1: block assigning the Owner role (and self-role changes) by non-owners.
5. H4: status guards, row locks and quantity validation on PO receive and transfer ship/receive/cancel.
6. H3: per-item refunded-quantity check, and no void after partial refund (lock inside transaction).
7. H7: use the Google session email in `CreateNewUser`, and do not auto-link or verify accounts that already have a password.
8. H2: remove the PIN-uniqueness oracle and throttle `setup/pin`.
9. H5: delete the duplicate `Event::listen` lines (double emails and double auto-POs today).
10. H6 + M5: make `migrate` include `database/migrations/tenant`, and use `filled()` for the inventory threshold fields.
