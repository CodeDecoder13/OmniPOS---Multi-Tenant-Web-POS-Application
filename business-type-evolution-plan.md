# OmniPOS: Business-Type Evolution Plan

Status: plan only, nothing built. Written 2026-10-26.

Goal: OmniPOS should feel built for each kind of business. A salon gets services, stylists, commissions and appointments. A cafe gets drink options and a kitchen screen. A pharmacy gets expiry dates. And when we add the next business type, it should cost one entry in one file, not a new project.

First request that started this: salons cannot record which stylist did a service, cannot take appointments, and cannot pay commissions. That is Phases B and C below.

## 1. How it works today (verified in the code)

| Fact | Where | Why it matters |
| --- | --- | --- |
| The business type is one enum with 17 types, each with a label, an icon and default categories | `app/Enums/BusinessType.php` | This is the natural home for everything type-specific |
| Each branch already has on/off feature toggles: POS, inventory tracking, kitchen display, dine-in, takeout, delivery, discounts, loyalty, receipt printing | `Branch::DEFAULT_SETTINGS`, `branch-feature` middleware, sidebar `feature` flags | Turning a module on or off per branch already hides it from the menu and routes. We reuse this |
| `delivery` and `customer_loyalty` toggles exist but nothing uses them | audit M-notes | They are free slots for future modules |
| The "Food" switch on a product really means "made to order, no stock tracking" | `is_food` in 5 places (inventory list, sale decrement, refund restock, product create/update) | Services have no stock either, so they are squeezed into "Food". Misleading label, and a salon cannot tell goods from services |
| The cashier who rings a sale is recorded, nobody else is | `orders.created_by`, `shifts` | There is no stylist, technician or attendant on a sale line |
| Changing the business type only swaps categories | `CategoryService::resetForBusinessType` | It should also offer the new type's recommended modules |

## 2. Design rules (lazy on purpose)

1. **One profile per business type, in the enum.** Add `BusinessType::profile()` returning: which modules are on by default, the words to use (terms), and starter data. Adding a business type = one enum case + one profile. No new tables for "business type".
2. **Reuse the branch feature toggles** for module switching. New modules (staff, appointments, commissions) become three more toggle keys. Owners can still override them in Branches > Feature Settings. No second flag system.
3. **Additive only.** New nullable columns and new tables. Existing shops see no change until they choose to turn something on.
4. **Words follow the business.** A small `terms` list per type (for example Order / Ticket / Service ticket, Table / Station / Chair, Product / Service / Menu item). The UI reads it from one shared prop. No per-page copies.
5. **Every new table has `tenant_id`**, every lookup is tenant scoped (`forTenant`, `TenantRule::exists`), every route has a permission, every multi-step write is a transaction. These are the rules the security audit enforced; new features must not reopen those holes.
6. **Build the smallest useful version first.** Anything that needs customers to log in (online booking, SMS reminders) is a later phase.

## 3. The capability catalogue

A "capability" is a switchable module. Status shows what exists today.

| Capability | Status | What it adds |
| --- | --- | --- |
| `inventory_tracking` | exists | stock counts, POs, transfers, suppliers |
| `kitchen_display` | exists | kitchen screen, KOT |
| `dine_in` (tables) | exists | table assignment |
| `service_items` | NEW (Phase A) | items with no stock, a duration, and no "Food" misnomer |
| `staff_tracking` | NEW (Phase B) | pick a staff member per sale line |
| `commissions` | NEW (Phase B) | commission rates and a payout report |
| `appointments` | NEW (Phase C) | calendar, bookings, check-out into the POS |
| `packages_memberships` | NEW (Phase D) | prepaid bundles and visit credits |
| `weighted_units` | NEW (Phase D) | sell by kg, metre, piece with decimals |
| `batches_expiry` | NEW (Phase D) | lot numbers and expiry dates |
| `serials_warranty` | NEW (Phase D) | IMEI/serial per unit, warranty end date |
| `job_tickets` | NEW (Phase D) | intake, status, pickup (laundry, repair) |
| `recipes` | NEW (Phase D) | bill of materials, batch yield |
| `open_tabs` | NEW (Phase D) | a bill that stays open (bar, restaurant) |
| `preorders_deposits` | NEW (Phase D) | custom orders with a deposit |
| `delivery`, `customer_loyalty` | toggle exists, unused | finish when a type needs them |

## 4. Which type gets what (defaults; owners can change)

On = on by default. Opt = available, off by default. Blank = not relevant.

| Business type | Inventory | Kitchen | Tables | Services | Staff | Commissions | Appointments | Type-specific packs |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Beauty Salon & Spa | Opt (retail only) | | | On | On | On | On | packages/memberships |
| Barbershop | Opt | | | On | On | On | On | walk-in queue |
| Pet Shop | On | | | Opt | Opt | Opt | Opt (grooming) | weighted food, vet notes |
| Laundry Shop | Opt (supplies) | | | On | Opt | | Opt | job tickets, weighed orders, pickup status |
| Electronics & Gadgets | On | | | On (repairs) | Opt | Opt | | serials/warranty, job tickets |
| Cafe / Coffee Shop | On | On | Opt | | | | | drink options (size, milk, sugar) |
| Milk Tea & Dessert | On | On | | | | | | sugar/ice/toppings presets |
| Restaurant | On | On | On | | Opt (servers) | Opt | Opt (reservations) | open tabs, split bills |
| Bar & Pub | On | Opt | On | | Opt | Opt | | open tabs, happy-hour pricing |
| Bakery | On | Opt | | | | | | preorders and deposits, custom cakes |
| Grocery | On | | | | | | | weighted items, barcode-first POS |
| Retail Store | On | | | | | | | barcode-first POS |
| Clothing & Accessories | On | | | | Opt | Opt | | size/color matrix, exchanges |
| Pharmacy | On | | | | | | | batches/expiry, prescription flag, senior/PWD rules |
| Hardware | On | | | | | | | units and weights, supplier credit |
| Food Manufacturing | On | | | | | | | recipes, batches, yield, packaging |
| Other | On | | | | | | | nothing extra |

## 5. Roadmap

Effort is in focused working days for one developer. Each phase ships on its own and can be reverted alone.

### Phase A: Foundation (2 to 3 days). Makes everything else cheap.
- `BusinessType::profile()` with `features`, `terms` and `starter` entries. Start with Beauty, Barbershop, Cafe and Restaurant filled in, everything else inheriting sane defaults.
- Apply the profile when a business registers, and offer "Apply recommended setup for your new business type" after a business-type change (never silently overwrite what the owner already chose).
- Share the profile to the front end once (Inertia shared prop) so the sidebar, POS and dashboard can read modules and terms.
- **Replace the "Food" misnomer.** Add `products.kind` (`goods`, `service`, `prepared`) or a simple `is_service` flag, and one `Product::tracksStock()` method; replace the 5 `is_food` checks with it. The product form shows "Goods / Service / Made to order" with plain help text. Existing food items keep working unchanged.
- Services get an optional `duration_minutes` (used later by appointments).
- Terms: first use in the sidebar, the dashboard titles and the POS cart header.
- Tests: profile defaults per type, `tracksStock()` for each kind, business-type change keeps owner overrides.

### Phase B: Staff and commissions (3 to 4 days). Answers the salon request.
- New `staff_members` table: tenant, name, optional linked login user, default commission rate, active flag. Stylists often have no login and should not use up paid user seats, so staff are their own list. Owners manage them under People.
- `order_items.staff_member_id` (nullable). In the POS, when `staff_tracking` is on and the line is a service, show a staff picker on the line (remembers the last choice for speed). Refunds and voids keep the link.
- Commission rules, smallest version: a default percent per staff member plus an optional per-service override. Commission is **calculated from sales at report time**, not stored, so refunds adjust it automatically (net of refunds, same rule as revenue).
- New report tab: **Staff performance**: services done, revenue, commission due, by date range, with CSV export. Permissions: `staff.manage`, `staff.report`.
- Optional second step later: a payout ledger (mark a period as paid) once owners ask.
- Security: staff ids validated with `TenantRule`, staff only visible inside their business.

### Phase C: Appointments (5 to 7 days). Salon, barber, spa, pet grooming first.
- Tables: `appointments` (branch, staff, customer or walk-in name and phone, start and end time, status `booked / arrived / in_service / completed / cancelled / no_show`, notes, resulting `order_id`) and `appointment_services` (appointment, service, price snapshot).
- Day and week calendar per staff member and per branch, drag to move, colour by status. Overlap check per staff member (one stylist cannot be double-booked) enforced inside a transaction with the same per-tenant lock used for order numbers.
- **Check out** button on an appointment opens the POS with its services and staff already filled in. Completing the sale marks the appointment completed.
- Business hours and staff days off (reuse the existing shift schedule data where possible).
- Plan gating: suggested Pro and Enterprise, Free shows the locked item (the upgrade prompt already exists).
- Not in this phase: customers booking online, SMS or email reminders.

### Phase D: Type packs. Order by how many shops each pack serves. Each is a self-contained module behind its own toggle.

| Pack | Types served | Smallest useful version |
| --- | --- | --- |
| D1 Drink options | Cafe, Milk Tea | Option presets (size, sugar, ice, toppings) built on the existing variation groups and add-ons, plus a one-click "apply preset" for new products |
| D2 Barcode-first retail | Grocery, Retail, Clothing, Hardware | Faster scan flow, decimal quantities and units (kg, m), size/color matrix on top of variations |
| D3 Preorders and deposits | Bakery, custom orders anywhere | Order with a pickup date, a deposit payment, balance due at pickup |
| D4 Open tabs | Bar, Restaurant | A bill that stays open across the evening and closes with split payments (held orders already store a cart; extend rather than rebuild) |
| D5 Job tickets | Laundry, Electronics repair | Intake with item description or weight, status (received, in progress, ready, picked up), pickup code, optional notification |
| D6 Serials and warranty | Electronics | Serial or IMEI per sold unit, warranty end date, lookup by serial |
| D7 Batches and expiry | Pharmacy, Grocery, Bakery | Lot and expiry per stock receipt, sell nearest-expiry first, expiry alert list |
| D8 Packages and memberships | Salon, Spa, Barber, Gym-like shops | Prepaid bundle with visit credits, redeemed at the POS |
| D9 Recipes and batches | Food Manufacturing, Bakery | Bill of materials per product, batch production consumes ingredients and yields finished stock |

Philippine-specific rules to confirm with an accountant before D7: VAT exemption on senior and PWD purchases of medicine, and how the existing percentage ID discounts should apply to pharmacy items.

### Phase E: Customer-facing and smart (later)
- Public booking page per business (a link or QR) with live staff availability.
- Appointment reminders by email, and SMS if you choose a provider.
- Dashboard widgets per type (top stylists, busiest hours, expiring stock, repeat customers).
- A deliberate review of whether to add more types: one profile entry each, plus any new capability it needs.

## 6. New data (summary)

| Table or column | Phase |
| --- | --- |
| `products.kind` (or `is_service`), `products.duration_minutes` | A |
| `staff_members`, `order_items.staff_member_id` | B |
| `appointments`, `appointment_services`, business hours | C |
| `customer_packages`, `package_redemptions` | D8 |
| `inventory_lots`, `serial_units`, `job_tickets`, `recipes`, `recipe_items` | D |

Every table carries `tenant_id` and a tenant index. Use the guarded-migration style from the tenant migrations (check before create) so deploys stay safe.

## 7. Permissions and plans

- New permission slugs: `staff.manage`, `staff.report`, `appointments.view`, `appointments.manage`, plus one per pack as built. They go through the existing permission seeder and the permission ceiling check.
- Suggested plan placement (your call): Free keeps services and basic sales; Pro adds staff and commissions; Enterprise adds appointments and packs. Locked items already show an upgrade prompt.

## 8. Quality rules for every phase

- Tenant isolation test for each new endpoint (a second business must get 404 for the first one's ids).
- State rules enforced on the server (an appointment cannot be completed twice, a commission cannot be negative).
- Money rounding and refund handling covered by one small test each.
- The tests currently fail on sqlite because migrations use Postgres-only syntax. Fix that first or run tests on Postgres in CI, otherwise none of the above can be proven automatically.
- Each phase: build passes, language files stay in sync (`npm run lang:check`), and a short note in the in-app release notes.

## 9. Risks

| Risk | Mitigation |
| --- | --- |
| Too many modules make the app feel crowded | Each type starts with only its own modules visible; everything else sits behind the existing Feature Settings |
| Changing business type surprises an owner | Offer, never force, the recommended setup; keep their overrides |
| Appointments are a big surface (time zones, overlaps) | Phase C stays staff-and-day-based first; times are stored in UTC and shown in the shop's timezone using the business-time helper already added |
| Commission disputes | Calculated from recorded sales, net of refunds, shown with the rule used; ledger only when asked |
| Scope creep per type | One pack at a time, only after a real shop of that type asks |

## 10. Decisions needed before Phase B

1. Are stylists a separate list from login users? (Recommended: yes, `staff_members`.)
2. Commission base: on price before discount, after discount, or after tax? Do tips exist and are they separate?
3. Should a service ever need two staff (assistant plus stylist) with a split?
4. Is a booking only for one staff member, or can a customer book "any available stylist"?
5. Which plan should staff, commissions and appointments sit on?
6. Do you want customers to book online soon (Phase E), or is a front-desk calendar enough for now?

## 11. Suggested order

A (foundation) first, then B and C, because they close the salon gaps and prove the profile idea. After that, take one pack at a time in the order your real customers ask for: drink options, barcode-first retail and open tabs are the cheapest wins; pharmacy batches, recipes and job tickets are the largest.
