# OmniPOS Security and Backend Audit

## Re-audit (post-fix) summary

Re-audit date: 2026-10-25. Commits reviewed: `3555fb3` (criticals), `ac0a549` (highs), `700dc9b` (mediums), compared with `git diff 3555fb3~1 HEAD`. Method: read-only. I read every changed file and the sibling controllers, FormRequests and services, and the Vue pages that call the changed endpoints. I ran `php -l` on all changed PHP files (all clean), `php artisan route:list` (262 routes, unchanged), `php artisan event:list` and `composer audit --locked` (no advisories). I did not run tests, migrations or anything against a database. `npm audit` was not run. Paths are relative to `myapp/` unless they start with `prod/`.

### (a) Verdict

The fixes are real and mostly correct. All four original cross-tenant critical holes (C1 user takeover, C2 inventory IDOR, C3 kitchen IDOR, and the named `exists:` rules in C4) are closed in the code. The PO and transfer state machines, the duplicate listeners, the tenant-folder migrations, Google email trust, the checkout money math and number generation are fixed properly, and the Vue pages still send the payload shapes the new guards expect. The work is not finished, though. One sibling endpoint the C4 sweep missed (`pos/hold`) still accepts another tenant's `customer_id`/`product_id`/`table_id`. The refund fix can be bypassed by repeating a line in one request. The PIN-uniqueness oracle is still there, only with a friendlier message. A `users.edit-role` holder can still take over coworkers' logins inside a tenant. The most urgent item is not in the code: `prod/env-prod.md` is tracked in a public GitHub repo and contains live object-storage credentials. On top of that, the seeded super-admin uses a known password and has no 2FA. Treat the app as "tenant isolation largely fixed, not yet safe to call secure" until the keys are rotated and the items in (e) are done.

### (b) Status of every earlier finding

| ID | Title | Original severity | Status now | Evidence / notes |
|---|---|---|---|---|
| C1 | Any tenant owner/manager can take over any user (email + password) | Critical | FIXED | `UserController.php:136-139` aborts 404 unless the target has a `tenant_users` row in this tenant. `UserController.php:205-213` skips email/password changes when the user also belongs to another tenant. Residual in-tenant takeover is tracked as N5. |
| C2 | IDOR on inventory adjust/history | Critical | FIXED | `InventoryController.php:32-35,65-67` take `int $inventory` and resolve through `InventoryService::findForTenant` (`InventoryService.php:65-70`, `forTenant()->findOrFail`). |
| C3 | IDOR on kitchen status and KOT PDF | Critical | FIXED | `KitchenDisplayController.php:64-72` `findOrderForUser` scopes by tenant and the user's branch, and is used at `:76` and `:94`. A user with no branch gets 404, which matches `index`/`poll` returning nothing for them. |
| C4 | Cross-tenant writes via unscoped `exists:` rules | Critical | PARTIAL | Every site named in the original is scoped now: `PurchaseOrderRequest.php:20-25`, `StockTransferRequest.php:20-24`, `ProductRequest.php:66,68`, `BranchMenuController.php:54`, `ShiftScheduleRequest.php:20-21` (user must be a tenant member), `UserController.php:96-97,151,176-177`. The sweep missed `HoldOrderRequest.php:23,35,38` (N1, High), `TableRequest.php:20` (N11) and the import rows `UserController.php:280-283` (N7). |
| H1 | `users.edit-role` holder can grant themselves Owner | High | PARTIAL | Self-role change is blocked (`UserController.php:181-185`), and only the owner can hand out the system Owner role (`:190-194`). Still possible: give any other role, including a custom all-permission role, to a coworker whose password you just reset (`:208-212`), then log in as them (N5). L12 (Admin edits their own role to add `settings.manage`) is still open. |
| H2 | PIN enumeration oracle | High | PARTIAL | `throttle:5,1` was added to `setup/pin` (`routes/tenant.php:40`) and the message is now generic. It is still an oracle: a 422 comes back only when the PIN matches another user's hash (`SetupController.php:80-85`, `PosPinController.php:89-97,129-133`). Three routes at 5/min each give about 15 guesses/min, so a 4-digit space falls in about 11 hours. `verify` still returns the name and permissions for any matching PIN. |
| H3 | Refund/void double restock | High | PARTIAL | Per-line refunded qty is computed (`RefundService.php:31-34`). Full refund uses only the remaining qty (`:44-55`), partial refund rejects over-qty (`:66-69`), void is blocked after any refund (`Order.php:71-75`), and void re-reads under lock (`OrderService.php:68-75`). Bypass: `RefundRequest.php:21` has no `distinct`, so `items:[{order_item_id:5,quantity:1},{order_item_id:5,quantity:1}]` on a qty-1 line passes both checks (the check reads only DB totals) and restocks 2 units (N6). |
| H4 | PO/transfer state machine | High | FIXED | Guards plus row locks: `PurchaseOrderService.php:85-86` (edit only Draft/Sent), `:119` (send only Draft), `:128-137` (receive only Sent/Partial, 0 <= qty <= outstanding), `:185` (cancel only Draft/Sent). `StockTransferService.php:75-90` (ship only Pending, qty <= requested and <= on hand under lock), `:126-131` (receive only InTransit, qty <= sent), `:171-172` (cancel only Pending/InTransit). `purchase-orders/Show.vue:24-35` and `stock-transfers/Show.vue:27-52` send `received_quantities`/`sent_quantities` keyed by item id, matching the services. The Edit button only shows for drafts. Leftovers: the controllers still pass raw `$request->input(...)` (a non-array value gives a 500), and receive/cancel stock writes are not row-locked (N8). |
| H5 | Listeners run twice | High | FIXED | `AppServiceProvider.php:44-51`. `event:list` now shows each `LowStockReached` listener and `SendAutoReceiptEmail` exactly once. |
| H6 | Tenant-folder migrations never run | High | FIXED (code), UNVERIFIED (prod) | `AppServiceProvider.php:41` `loadMigrationsFrom(database_path('migrations/tenant'))`. All 20 tenant files have a `hasTable`/`hasColumn` guard (the guard in `000055` predates the fix), so on a DB that already has the tables but no `migrations` rows they no-op and get recorded. Filename sort keeps `000045` tables before central `000046` and `000047` promotions before `000048`. Not run against a copy of production. Their `down()` methods drop live tables, so never run `migrate:rollback` in prod (N17). |
| H7 | Google registration/link email trust | High | FIXED | `CreateNewUser.php:36-39` forces the session's Google email. `GoogleController.php:35-39` requires `email_verified`. `:51-53` nulls the password when linking an unverified account. |
| M1 | Negative totals, client-priced modifiers | Medium | FIXED | `PosService.php:211-219` caps the discount at the subtotal and clamps at 0. `:113-116,151-188` eager-load `variationGroups.options` and `addons` and price modifiers on the server, matched by the names the POS sends (`pos/Index.vue:364-373,955-960`, taken from product data, so exact-match works; recall of held items uses the stored names). Notes: an option or add-on renamed after an order was held makes recall checkout fail with a clear message. Inactive add-ons are still accepted, at their real price. |
| M2 | Number generation races, UTC prefix | Medium | FIXED | `app/Support/TenantLock.php` (`pg_advisory_xact_lock`). Every caller is inside `DB::transaction` (`PosService.php:95/110`, `RefundService.php:23/36`, `PurchaseOrderService.php:51/62`, `StockTransferService.php:50/53`, `HeldOrderService.php:17/22`). Dates use `now('Asia/Manila')`. Trade-off: every checkout in a tenant is now serialized for the whole checkout transaction (N15). |
| M3 | "Today" and grouping in UTC | Medium | OPEN | `config/app.php:68` still `'timezone' => 'UTC'`. Only the number prefixes moved to Manila. |
| M4 | Shift open/close for any user | Medium | FIXED | `ShiftController.php:52-66` `assertMayActFor` (self, the PIN-verified operator via `pos_verified_operator_id`/`_at` set in `PosPinController.php:44-45`, or the owner), used at `:78,102`. `ShiftService.php:22-50` serializes open with an advisory lock and `:57-63` locks on close. The POS PIN-switch flow still works. Residual: the owner can open a shift for a `user_id` that is not a member (no membership check), and `pos/shifts/status?user_id=` leaks other users' shift cash (N12). |
| M5 | Inventory adjust 500 on cleared threshold | Medium | PARTIAL | `InventoryController.php:49-57` uses `filled()`, so no more 500. The stock adjust and the threshold update are still two separate commits. `like` is still case-sensitive on Postgres (`InventoryService.php:31-32`). |
| M6 | Product delete loses image, FK 500 | Medium | FIXED | `ProductService.php:132-143` refuses when PO/transfer lines exist, deletes the row and then the file. `ProductController.php:153-157` shows a friendly error. |
| M7 | Business-type reset destroys history | Medium | PARTIAL | Held orders are deleted and product images removed after commit (`CategoryService.php:39-67`), and the UI warns (`settings/Index.vue:479`). PO and transfer lines are still hard-deleted, so those headers keep totals with no lines. |
| M8 | Reports: pluck of all ids, gross revenue | Medium | PARTIAL | Subqueries in `ReportService.php:80,110`. Net revenue in `getSummary`/`getSalesTrend` (`:23-25,59`, `COALESCE` safe, column `refunded_amount` exists). Still gross: `getOperatorPerformance` (`:131-133`) and `getBranchComparison` (`:163-164`). Date range still unbounded (`ReportController.php:24-25`). |
| M9 | Missing FK indexes | Medium | FIXED (code), UNVERIFIED (prod) | `database/migrations/2026_10_25_000001_add_performance_indexes.php`, guarded with `hasColumns`/`hasIndex`. Plain `CREATE INDEX` takes a write lock while it builds, so run it off-peak. |
| M10 | Promo race gives free paid plan | Medium | FIXED | `RegisterService.php:50-64` locks the code and throws inside the transaction. New side effect: the `User` row is created before that transaction (`CreateNewUser.php:74-78`), so a lost race leaves an orphan user with no tenant (N9). |
| M11 | Chat unthrottled, hardcoded recipients, public disk | Medium | PARTIAL | `throttle:20,1` (`routes/tenant.php:352`). Recipients come from `config('mail.support_recipients')` (`config/mail.php:148-151`, env `SUPPORT_NOTIFY_EMAILS`, the default still lists the two personal addresses). Chat attachments and receipt logos are still on the ephemeral `public` disk (`ChatService.php:77-80`, `ReceiptCustomizationController.php:60-63`). |
| M12 | `php artisan serve` in production | Medium | PARTIAL | `railway.toml:7` adds `PHP_CLI_SERVER_WORKERS=4`. It is still the PHP dev server. |
| M13 | Setup usable by any tenant user | Medium | FIXED | `SetupController.php:51-53` 403 once any branch exists. |
| L1 | Receipt email unthrottled; stale-shift cash 0 | Low | OPEN | `routes/tenant.php:185` no throttle. `CloseStaleShifts.php:33` still `ending_cash => 0`. |
| L2 | Public receipt route unthrottled | Low | OPEN | `routes/web.php:126-128`. |
| L3 | TrackPageVisit on healthcheck, raw IP | Low | OPEN | `railway.toml:8` `healthcheckPath = "/"`. |
| L4 | Dashboard has no permission | Low | OPEN | `routes/tenant.php:42`. |
| L5 | Kitchen `since` unvalidated | Low | OPEN | `KitchenDisplayController.php:53`, `KitchenDisplayService.php:38-39`. |
| L6 | Import does not re-validate rows | Low | OPEN | `UserImportService.php:108-130` trusts `email`, `role_id` and `branch_id`. Raised to Medium as N7 because `branch_id` can point at another tenant. |
| L7 | OTP plaintext, `!==` | Low | OPEN | `VerifyEmailOtpController.php:31`. |
| L8 | `stancl/tenancy` installed, unconfigured | Low | OPEN | `composer.json:21`. `event:list` still shows its queue bootstrapper hooks. |
| L9 | Session secure cookie / APP_DEBUG env-driven | Low | UNVERIFIED | `config/session.php:172`. Needs the Railway variables, which this review cannot read. |
| L10 | `"*"` constraints | Low | OPEN | `composer.json:13,17`. `composer audit` reports no advisories today. |
| L11 | N bcrypt checks per PIN verify | Low | OPEN | `PosPinController.php:25-31`. |
| L12 | `roles.edit` can extend own role | Low | OPEN | `RoleService.php:47-70` has no self-role guard. The default Admin role holds `roles.edit`, so an Admin can add `settings.manage` to Admin. |
| O1 | Stock movement in 7 copies | Overlap | PARTIAL | Locks were added to transfer ship only. PO receive, transfer receive and transfer cancel still read-modify-write without a lock (N8). |
| O2 | Number generation in 5 copies | Overlap | PARTIAL | All five now share `TenantLock`, but there are still five generators. |
| O3 | Sales stats in 6 places | Overlap | OPEN | Unchanged. Report net revenue now differs from dashboard and shift numbers. |
| O4 | Promotion math duplicated | Overlap | OPEN | Unchanged. |
| O5 | Signup promo validation x3 | Overlap | PARTIAL | `RegisterService` now locks and is authoritative. The other two copies remain. |
| O6 | PIN uniqueness check x3 | Overlap | OPEN | Still three copies and still the H2 oracle. |
| O7 | Owner checks duplicated | Overlap | OPEN | More copies were added (`ShiftController`, `UserController`). |
| O8 | Low-stock definition x3 | Overlap | OPEN | Unchanged. |
| O9 | Listener registration twice | Overlap | FIXED | Same as H5. |
| O10 | No tenant `exists` helper | Overlap | OPEN | Rules are still written inline, which is how `HoldOrderRequest` and `TableRequest` were missed. |

### (c) Counts (46 earlier items: 4 C, 7 H, 13 M, 12 L, 10 overlaps)

- FIXED: 15 (C1, C2, C3, H4, H5, H6, H7, M1, M2, M4, M6, M9, M10, M13, O9). H6 and M9 are fixed in code and unverified in production.
- PARTIALLY FIXED: 12 (C4, H1, H2, H3, M5, M7, M8, M11, M12, O1, O2, O5).
- NOT FIXED / OPEN: 18 (M3, L1, L2, L3, L4, L5, L6, L7, L8, L10, L11, L12, O3, O4, O6, O7, O8, O10).
- WON'T FIX / BY DESIGN: 0.
- UNVERIFIED: 1 (L9, needs the Railway environment).

### (d) New findings

- N1. High. Cross-tenant references in held orders. `app/Http/Requests/Tenant/HoldOrderRequest.php:23,35,38` validate `items.*.product_id`, `customer_id` and `table_id` as plain integers. `HeldOrderService.php:17-34` stores them, and `:65` returns `customer:id,name`. Scenario: any self-registered owner posts `/A/pos/hold` with `customer_id` = N for N = 1..max and reads every other tenant's customer names from the response. In the victim tenant, `CustomerService::getOrderHistory` (`CustomerService.php:75-82`, unscoped `$customer->orders()`) now lists the attacker's held order (attacker-chosen product names and notes), and `CustomerService.php:51` refuses to delete that customer. Fix: copy the scoped rules from `CheckoutRequest.php:41,51,66` (`Rule::exists(...)->where('tenant_id', $tenant->id)`) into `HoldOrderRequest`. As a second guard, scope `Customer::orders()` reads with `forTenant`.
- N2. Critical (operational). Live object-storage credentials are committed to a public repo. `prod/env-prod.md` is tracked (added in commit `3bba05e`) and holds non-empty `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_URL`, `AWS_DEFAULT_REGION`, `AWS_USE_PATH_STYLE_ENDPOINT` and `PRODUCT_STORAGE_DISK` (values not reproduced here). Anyone can read, overwrite or delete every tenant's product images and any other object in that bucket. Fix: rotate the key pair at the storage provider now, delete the file, add `prod/` to `.gitignore`, and optionally purge it from history. Deleting the file does not help without rotation, because forks and clones keep it.
- N3. High (operational, unverified in prod). Known super-admin credentials. `database/seeders/AdminSeeder.php:12-18` creates `admin@omnipos.com` with password `password`. The admin login (`SuperAdmin/Auth/LoginController.php:19-33`) has a 5/min throttle but no 2FA. If the seeder was ever run against production, anyone can take over the whole platform: every tenant and user, password resets (`SuperAdmin/UserController.php:104-118` shows the new password in a flash), and creating admins. I could not verify whether the seeder ran in production (`railway.toml` only runs `migrate`). Fix: change or remove that account in production, make the seeder read the password from env and refuse to run in production, and add 2FA for admins.
- N4. Medium. Admin `is_active` is never enforced. `AdminRequest.php:21` and `Admin.php:14,27` support deactivation, but `LoginController.php:25` calls `attempt($credentials)` without `is_active`, and `EnsureUserIsAdmin.php:14` only checks the guard. A "deactivated" admin keeps full access. Fix: `attempt([...$credentials, 'is_active' => true])`, plus `abort_unless(Auth::guard('admin')->user()->is_active, 403)` in the middleware.
- N5. Medium. In-tenant account takeover by `users.edit-role`. `UserController.php:202-213`: any holder of `users.edit-role` (included in the default Admin role) can set a new email and password for any non-owner coworker who is not in another tenant, including other Admins, then sign in as them. Together with free role assignment (`:187-194`), this sidesteps the H1 self-role block. Fix: let only the owner change another user's email or password, and send a password-reset link instead of setting a password. Do not let a non-owner assign a role whose permissions exceed their own.
- N6. Medium. Refund duplicate-line bypass (H3 residual). `RefundService.php:58-79` with `RefundRequest.php:21`. Fix: add `'items.*.order_item_id' => [..., 'distinct']`, or sum the requested qty per `order_item_id` before the remaining check.
- N7. Medium. Import accepts another tenant's branch and role ids, and several lookups trust `tenant_users.branch_id` unscoped. `UserController.php:280-283` validates `rows.*.role_id`/`branch_id` as bare integers, and `UserImportService.php:124-129` stores them. Many places call `Branch::find($tenantUser->branch_id)` without a tenant scope: `PosController.php:38`, `KitchenDisplayController.php:30`, `PosService.php:78,343`, `CheckBranchFeature.php:20`, `HandleInertiaRequests.php:137`. An owner can import a user bound to a victim's branch and read that branch's name, feature settings and per-branch menu prices through the POS. Fix: re-run `validateCsv` server side (the original L6 fix) and replace those `Branch::find` calls with `Branch::forTenant($tenant)->find(...)`.
- N8. Medium. Lost-update races on stock. `PurchaseOrderService.php:146-153` (receive), `StockTransferService.php:135-142` (receive) and `StockTransferService.php` cancel (about `:178-186`) read `quantity_on_hand` without `lockForUpdate` and write back an absolute value, while checkout locks the same row. A concurrent sale can be overwritten. Fix: `lockForUpdate()` on the inventory row, or `increment('quantity_on_hand', $n)`.
- N9. Low. Orphan user on registration failure. `CreateNewUser.php:74-88` creates the user (and marks Google users verified) outside `RegisterService`'s transaction. When the new promo check throws, the user stays with no tenant and cannot re-register with that email. Fix: wrap both in one `DB::transaction`.
- N10. Low. `/register` has no rate limit. `config/fortify.php:117-120` only defines `login` and `two-factor` limiters, and each signup seeds roles, categories and promotions. Fix: add a `throttle:5,1` limiter to the register route.
- N11. Low. `TableRequest.php:20` `branch_id` is unscoped. A table can be attached to another tenant's branch id. I found no read path that exposes it, but scope it like the others.
- N12. Low. `pos/shifts/status?user_id=` (`ShiftController.php:110-127`) returns any coworker's open shift and cash summary to any `pos.access` user. Fix: apply `assertMayActFor`, or require `shifts.view`.
- N13. Low. No reserved slugs. `RegisterService.php:80-91` lets a store named "Admin" get slug `admin`, whose tenant URLs collide with `/admin/*` (admin routes are registered first). This only breaks that tenant. Fix: a reserved-word list (`admin`, `login`, `register`, `receipts`, `auth`, `email`, `settings`, `up`, `dashboard`).
- N14. Low. Business-rule failures use `abort(422, ...)` on Inertia form posts (PO, transfer, refund, void, shift). The user sees a generic error page instead of a field error. This is a UX regression from the fixes, not a security issue. Fix: throw `ValidationException::withMessages` or `back()->with('error', ...)`.
- N15. Low (capacity). The `TenantLock` taken in `PosService::generateOrderNumber` (`PosService.php:110,387`) is held until the checkout transaction commits, so every checkout in a tenant runs one at a time. That is fine for small shops and becomes a ceiling for busy multi-branch tenants. Upgrade path: a per-tenant counter row updated last, or a sequence.
- N16. Low. Admin password reset shows the plaintext new password in a flash message (`SuperAdmin/UserController.php:117`), so it lands in the session store. Prefer sending a reset link.
- N17. Low (operational). Tenant-folder migrations now registered in `migrations` have `down()` methods that drop tables created long ago outside this migration run. A `migrate:rollback` in production would drop live data. Do not roll back in production. Make those `down()` methods no-ops if you want to be safe.

Checked and fine: no other unscoped `exists:`/`unique:` rules (the remaining ones point at global tables: users, plans, admins, permissions, promo_codes). No implicit route-model binding of tenant models remains in any controller. No CSRF exemptions. No `unserialize` and no SSRF sinks. No open redirects (`intended()` only). The only debug routes are local-only mail previews (`web.php:105`). Upload rules use `image|max`. `composer audit` is clean.

### (e) Is it safe and secure?

Honest answer: not yet. It is much safer than before the fixes.

Reasonably safe now: tenant isolation on the paths the first audit named (users, inventory, kitchen, PO, transfers, products, branch menu, schedules); the inventory state machines; checkout pricing and totals; refund and void money caps; Google sign-in; shift ownership; duplicate emails and auto-POs; fresh-database migrations.

Still risky: the leaked storage keys (anyone on the internet can use them today), the possible default super-admin password with no 2FA, cross-tenant customer-name enumeration and data injection through `pos/hold`, in-tenant account takeover by Admins, refund restock inflation through duplicate lines, the PIN oracle, and running production on the PHP dev server.

Top 10 remaining actions, in priority order:

1. Rotate the object-storage access key and secret now, then remove `prod/env-prod.md` from the repo and ignore `prod/` (N2).
2. Change or delete the seeded `admin@omnipos.com` account in production, enforce admin `is_active`, and add admin 2FA (N3, N4).
3. Scope `HoldOrderRequest` `product_id`, `customer_id` and `table_id` to the tenant (N1). Same one-line pattern as `CheckoutRequest`.
4. Add `distinct` (or per-line aggregation) to refund items (N6/H3).
5. Make another user's email and password owner-only, and stop non-owners assigning roles above their own; guard self-role edits in `RoleService` (N5, H1, L12).
6. Railway environment: `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`, a strong `APP_KEY`. Unverified from here (L9).
7. Before deploying `ac0a549`/`700dc9b`, run `php artisan migrate --pretend` and then `migrate --force` on a copy of the production database to confirm the tenant-folder guards and the new indexes apply cleanly. Run the index migration off-peak (H6, M9, N17).
8. Move production off `php artisan serve` to FrankenPHP/Octane or nginx + php-fpm, run the queue worker as its own service, and point the healthcheck at `/up` (M12, L3).
9. Remove the PIN-uniqueness oracle entirely and verify PINs per selected user, which also fixes the N-bcrypt cost (H2, L11, O6). Validate the import rows server side and scope the `Branch::find` calls (N7, L6).
10. Lock inventory rows in PO receive and transfer receive/cancel (N8), then fix the timezone (M3), the report gross-revenue leftovers and range cap (M8), and move chat and receipt uploads to the S3 disk (M11).

---

Scope: `myapp/` (Laravel 12.70.1, Inertia/Vue). Read-only review. All paths are relative to `myapp/`.
Method: read routes, middleware, controllers, services, FormRequests, models, migrations; ran `php artisan event:list` and `php artisan route:list --json` (read-only).
Tests: `tests/` only contains Auth, Settings, Dashboard and Example tests. There are no Inventory, Category, or business-type tests, so the recent changes cannot have broken existing tests and `--filter=Inventory` would run nothing. phpunit.xml uses sqlite `:memory:` (safe), but tests were not run because there is nothing relevant to run.

Severity key: Critical = cross-tenant compromise or account takeover by any signed-up user. High = data corruption or privilege escalation inside a tenant, or cross-tenant writes needing a permission. Medium = correctness or abuse with real impact. Low = hardening.

Note: anyone can self-register and becomes the Owner of a new tenant with every permission (`IdentifyTenant.php:56-57`). So "requires permission X" never protects against cross-tenant attacks: an attacker simply registers a tenant.

---

## Critical

### [FIXED] C1. Any tenant owner/manager can take over ANY user account in the system (email + password change)
- Evidence: `app/Http/Controllers/Tenant/UserController.php:164-190`. For a non-owner `$userId`, the code runs `User::findOrFail($userId)` (line 182) and `$user->update(['email'=>..., 'password'=>...])` (line 190). Nothing checks that `$userId` is a member of the current tenant. The `TenantUser` update on lines 192-197 is scoped, but the `User` update is global.
- Scenario: attacker registers tenant A (gets Owner, so `users.edit-role`). `PUT /A/users/123` with `{name, email:"attacker@x.com", password:"...", role_id:<A's role>}` where 123 is the owner of tenant B. User 123's email and password are overwritten, and the attacker logs in as B's owner. User ids are sequential, so every account can be enumerated.
- Fix: at the top of `update()` (and the same for `toggleActive`/`remove`, which are already scoped but should share this guard):
  ```php
  abort_unless(TenantUser::where('tenant_id', $tenant->id)->where('user_id', $userId)->exists(), 404);
  ```
  Also stop letting a tenant manager change another user's global email/password. Users are global across tenants, so changing them should be owner-only at minimum.

### [FIXED] C2. IDOR on inventory adjust/history (implicit binding, no tenant check)
- Evidence: `routes/tenant.php:137,139` bind `{inventory}`. `InventoryController.php:32,62` type-hints `Inventory $inventory`. `Inventory` has no global scope or `resolveRouteBinding` (`app/Models/Tenant/Inventory.php`), and `InventoryService::adjust()` (line 72) and `getHistory()` (line 219) do not check the tenant.
- Scenario: tenant A user `POST /A/inventory/999/adjust {"type":"damage","quantity_change":-50}`, where 999 belongs to tenant B. B's stock is changed, an adjustment row is written under B's tenant_id, and thresholds can be nulled. `GET /A/inventory/999/history` leaks B's adjustment history including creator names.
- Fix: change the signature to `int $inventory` and resolve with the existing `$this->inventoryService->findForTenant($tenant, $inventory)` (`InventoryService.php:65`), which already exists but is unused.

### [FIXED] C3. IDOR on kitchen status update and KOT PDF
- Evidence: `routes/tenant.php:327-328`, `KitchenDisplayController.php:63,78` bind `Order $order` with no tenant or branch check. `KitchenDisplayService::updateStatus()` (line 45) and `KotService::generatePdf()` (line 12) trust the model.
- Scenario: `PATCH /A/kitchen/5000/status {"kitchen_status":"served"}` changes tenant B's order. The JSON response returns the full fresh `Order` (totals, customer_id, notes). `GET /A/kitchen/5000/kot/pdf` downloads B's order items.
- Fix: `int $order` plus `Order::forTenant($tenant)->where('branch_id', $tenantUser->branch_id)->findOrFail($order)`.

### [PARTIAL] C4. Cross-tenant inventory writes via purchase orders and stock transfers
- Evidence: `app/Http/Requests/Tenant/StockTransferRequest.php:17-21` and `PurchaseOrderRequest.php:17-22` use global `exists:branches,id`, `exists:products,id`, `exists:suppliers,id`. `StockTransferService::ship()` (lines 78-85) does `Inventory::firstOrCreate(['product_id'=>..., 'branch_id'=>$transfer->source_branch_id])` and decrements it. `PurchaseOrderService::receive()` (lines 134-141) increments.
- Scenario: attacker creates a transfer with `source_branch_id` = victim branch and `product_id` = victim product, then calls ship. The victim's stock is decremented, and with no lower bound it can go negative. A PO with the victim's branch and product, once received, inflates the victim's stock. PO and transfer show pages also leak the victim's branch, supplier and product names and SKUs through the eager loads.
- Fix: scope every `exists` rule, e.g. `Rule::exists('branches','id')->where('tenant_id', $tenant->id)` (the same pattern `CheckoutRequest.php:41` already uses). Apply it to `PurchaseOrderRequest`, `StockTransferRequest`, `ProductRequest.php:66,68` (`addon_ids`, `branch_ids`), `BranchMenuController.php:53`, `ShiftScheduleRequest.php:18` (and add a tenant-membership check for `user_id` on line 17), and `UserController.php:96-97,145,170-171` (later code partially re-checks these, but the rules should be scoped).

---

## High

### [PARTIAL] H1. Privilege escalation: a `users.edit-role` holder can grant themselves the Owner role
- Evidence: `UserController.php:166-197`. The only owner guard is for editing the owner's own row (line 136). A manager editing their own row (`$isOwnerRow` is false) may set `role_id` to the tenant's system `owner` role, and `IdentifyTenant.php:56` then grants every permission.
- Scenario: a manager with `users.edit-role` sends `PUT /A/users/{self} {role_id: <owner role id>}` and gets full permissions, including settings, roles and business-type reset (the last is owner_id-gated, but everything else opens up).
- Fix: in `update()` and `store()`, reject `$role->is_system && $role->slug === 'owner'` unless the actor is `$tenant->owner_id`. Also forbid non-owners from editing their own role. `UserImportService::import()` (line 111) also trusts client `role_id`/`branch_id` (it is owner-only, but should re-run `validateCsv` server side).

### [PARTIAL] H2. PIN enumeration oracle (unthrottled) leads to POS operator impersonation
- Evidence: `SetupController.php:64-89` (`POST {tenant}/setup/pin`) has no permission and no throttle (`routes/tenant.php:40`). It returns "This PIN is already in use by another user" when the submitted PIN matches anyone's hash. `PosPinController::set` (lines 84-97) is the same oracle with throttle 5/min, and `verify` returns the owner's name and permissions for a matching PIN.
- Scenario: a cashier scripts 10,000 POSTs to `/A/setup/pin` (4-digit space) and learns every PIN in use. They then call `pos/pin/verify` once per PIN to map PINs to names, and check out as another operator (`PosController.php:97-113`), attributing sales and cash to someone else.
- Fix: drop the uniqueness check entirely (PINs are verified by identity plus PIN, or per-user lookup), or at least return a generic error, add `throttle:5,1` to `setup/pin`, and return 404 from `setup` POSTs once a branch exists. Also consider requiring the operator to pick their user, then verify only that user's hash (this also removes the N x bcrypt CPU cost per attempt in `verify`).

### [PARTIAL] H3. Refund and void double-restock and over-refund per item
- Evidence: `RefundService.php:45-61` caps only the money total (lines 68-71). It never checks per-item already-refunded quantity, and the requested quantity is never compared with `orderItem->quantity` (`RefundRequest.php:22` only has `min:1`). A "full" refund after a partial (lines 32-41) restocks the full quantity again. `Order::canBeVoided()` (`Order.php:71-74`) allows voiding a partially refunded order, and `incrementForVoid` (`InventoryService.php:172`) restocks the full quantities again.
- Scenario: order of 1 x A (100) + 1 x B (100). Partial refund A qty 1, then again A qty 1. Both pass the money cap (200), A is restocked twice and B never comes back. Or: partial refund, then void, and items are restocked twice. With `{quantity: 50}` on one item the money cap still applies, but inventory restock is +50.
- Fix: in partial refunds compute `$alreadyRefunded = RefundItem::where('order_item_id',$id)->sum('quantity')` and reject `qty > item.quantity - alreadyRefunded`. For "full", refund only the remaining quantities. Make `canBeVoided()` also require `refunded_amount == 0`. Move `voidOrder`'s check inside the transaction with `lockForUpdate()` like `RefundService.php:22` does (it currently allows a double void race that restocks twice).

### [FIXED] H4. PO receive and stock transfer ship/receive have no state machine, so stock can be replayed or forged
- Evidence: `PurchaseOrderController.php:102-110` and `StockTransferController.php:64-95` call the service with raw `$request->input('received_quantities'|'sent_quantities')` (no validation). The services (`PurchaseOrderService.php:119`, `StockTransferService.php:71,110`) never check the current status.
- Scenario: POST `/receive` twice on a Received or Cancelled PO and stock is added twice. Post `received_quantities: {12: -500}` and stock is decremented below zero. Ship a transfer twice and the source is decremented twice. Receive a Pending (never shipped) transfer with `{id: 999}` and stock is created from nothing. `update()` on a Received PO rewrites its items.
- Fix: guard each transition (`abort_unless($po->status === Sent || $po->status === Partial, 422)`, `ship` only from Pending, `receive` only from InTransit, `cancel` not from Completed). Lock the row (`lockForUpdate`) inside the transaction, validate quantities as `integer|min:0` with `received <= ordered - already_received` and `sent <= on_hand`.

### [FIXED] H5. Every low-stock and auto-receipt listener runs twice (auto-discovery plus explicit registration)
- Evidence: `php artisan event:list` shows `LowStockReached` with `SendLowStockDatabaseNotification`, `SendLowStockEmailNotification` and `CreateAutoReorderPurchaseOrder` each registered twice, and `OrderCompleted -> SendAutoReceiptEmail` twice. Cause: `AppServiceProvider.php:42-48` calls `Event::listen` for listeners that Laravel 12 already auto-discovers from `app/Listeners`.
- Scenario: every sale that crosses a threshold sends two emails and two DB notifications, and races two auto-reorder jobs (the "existing PO" check in `CreateAutoReorderPurchaseOrder.php:33-39` is not locked, so duplicate POs are possible). Every customer gets two receipt emails.
- Fix: delete the six discovered `Event::listen` lines in `AppServiceProvider::registerEvents()`. Keep only `LogOrderActivity` if it is not discovered (it is listed once, so discovery does not pick it up). Verify afterwards with `php artisan event:list`.

### [FIXED] H6. Fresh deploy or new environment cannot migrate; tenant-folder migrations are never run
- Evidence: `database/migrations/tenant/*` (suppliers, promotions, tables, refunds, purchase orders, stock transfers, variations, addons, notifications) is a subdirectory. `php artisan migrate --force` (`railway.toml` preDeployCommand) only scans `database/migrations`, and nothing calls `loadMigrationsFrom` (there is no `config/tenancy.php`). Central migrations reference these tables, e.g. `2025_01_01_000046_add_table_id_to_orders_table.php:14` (FK to `tables`) and `000048` (FK to `promotions`).
- Scenario: a new Railway environment, a DB restore, or a CI Postgres run fails at migration 000046 ("relation tables does not exist"), or ends up missing tables. Production only works because those tables were created some other way.
- Fix: move the files into `database/migrations/` (keeping their timestamps), or add `$this->loadMigrationsFrom(database_path('migrations/tenant'))` in `AppServiceProvider::boot()`. Most files already use `hasTable`/`hasColumn` guards. Then remove unused `stancl/tenancy` (see L8).

### [FIXED] H7. Google registration lets an attacker create a verified account for any email, plus a pre-hijack via Google link
- Evidence: `CreateNewUser.php:33-79` takes `email` from form input but marks it verified because `session('google_user')` exists (lines 73-77). The Google email in the session is never compared. `GoogleController.php:35-56` links Google to any existing account by email and force-verifies it, even if that account was created unverified with an attacker-chosen password.
- Scenario 1: attacker completes Google OAuth with their own account (new user), then submits `/register` with `email=ceo@victim.com`. The account is created as verified, and the store is registered under that email.
- Scenario 2: attacker registers `victim@gmail.com` with a password (unverified). Later the victim signs in with Google, the account is linked and verified, and the attacker's password still works.
- Fix: in `CreateNewUser`, when `$isGoogleRegistration`, set `$input['email'] = $googleUser['email']` (ignore the form). In `GoogleController`, when linking to an existing unverified account, reset its password (`password => null`) or refuse to link. Also check `$googleUser->user['email_verified'] ?? false`.

---

## Medium

### [FIXED] M1. Checkout money integrity: negative totals and client-priced modifiers
- Evidence: `PosService.php:185-193`: a fixed `discount_amount` is not capped at the subtotal (`CheckoutRequest.php:61` is only `min:0`), so `$afterDiscount` and `total` can go negative and still pass the payment check (lines 241-251). Variation and add-on prices come from the client (`PosService.php:151-165`, `CheckoutRequest.php:46,49`) and are never looked up from `variation_options`/`addons`.
- Scenario: `discount_type=fixed, discount_amount=1000` on a 100 order stores `total=-900` (when discounts are enabled for the branch) and corrupts revenue, tax and shift cash. Sending `addon_price: 0` for a paid add-on undercharges.
- Fix: `$discountAmount = min($discountAmount, $subtotal)` and `$total = max(0, $total)`. Resolve modifier and add-on prices server side by id (`VariationOption::whereIn(...)`, `Addon::forTenant(...)`) instead of trusting the snapshot fields.

### [FIXED] M2. Order, refund, PO and transfer number generation races; UTC date prefixes
- Evidence: `PosService.php:359-378` and `RefundService.php:167-186` lock only the last existing row (nothing to lock for the first order of the day, and in Postgres READ COMMITTED two waiters both read the same max). `PurchaseOrderService.php:176` and `StockTransferService.php:188` have no lock at all. `orders` has `unique(tenant_id, order_number)` (`create_orders_table.php:28`), so the race surfaces as a 500 on checkout. `now()` is UTC (`config/app.php:68`), so in Manila the "day" in the number rolls over at 08:00.
- Fix: use a per-tenant counter row updated with `lockForUpdate()` (or `pg_advisory_xact_lock(hashtext(tenant_id))` at the start of the transaction) in one shared helper. Format with `now('Asia/Manila')` or the tenant's timezone.

### [OPEN] M3. "Today" and day-grouping use UTC
- Evidence: `config/app.php:68` `'timezone' => 'UTC'`. `DashboardController.php:53-79` (`Carbon::today()`, `whereDate`, `DATE(created_at)`), `PosController.php:196` (billing history "today"), `ReportService.php:450-451` (`startOfDay`), `date_trunc` in `ReportService.php:58` and `AIInsightsService`. The `reports:daily-summary` schedule at 23:30 UTC is 07:30 Manila the next day (`routes/console.php:12`).
- Scenario: a sale at 07:00 Manila on the 5th is reported as the 4th. The dashboard "Today" is empty until 08:00 local.
- Fix: the smallest fix is `'timezone' => env('APP_TIMEZONE', 'Asia/Manila')` (Postgres `timestamp without time zone` columns then store local time consistently). The better fix is to keep UTC storage and pass `->timezone($tz)` boundaries into queries, plus `date_trunc(..., created_at AT TIME ZONE 'Asia/Manila')`.

### [FIXED] M4. Shift open/close can target any user or shift in the tenant
- Evidence: `ShiftController.php:53-60` takes `user_id` from the request without checking tenant membership or PIN verification. `close()` (lines 69-80) closes any tenant shift id with a caller-supplied `ending_cash`. `openShift` has a check-then-insert race (`ShiftService.php:21-44`), and `closeShift` checks `isOpen()` outside the transaction without a lock (lines 52-56).
- Fix: require `user_id` to be the authenticated user or the session's `pos_verified_operator_id` (same rule as `PosController.php:97-113`). Restrict `close` to the shift owner or `shifts.view`/owner. Wrap the open check in a transaction with `lockForUpdate`, or add a partial unique index `(tenant_id, user_id) WHERE status='open'`.

### [PARTIAL] M5. Inventory adjust 500s when a threshold field is cleared (recent change)
- Evidence: `InventoryController.php:46-48` uses `$request->has('low_stock_threshold')`, and the rule is `nullable` (`InventoryAdjustmentRequest.php:28`), so an explicit `null` is written to `low_stock_threshold`, which is `integer NOT NULL default 0` (`create_inventory_table.php:18`). Postgres then throws a not-null violation and the whole request returns 500. This happens after `adjust()` already committed the stock change in its own transaction, so the user sees an error while stock did change.
- Fix: `if ($request->filled('low_stock_threshold'))`, or drop `nullable` from that one rule. Wrap the adjust call and the threshold update in one `DB::transaction`.
- Also in the same area: `InventoryService::scopedQuery` (`InventoryService.php:31-32`) uses `like`, which is case-sensitive on Postgres ("coke" will not find "Coke"). Use `ilike` (other services have the same issue). `stats()` counts zero-stock items with threshold 0 as "in stock" (line 45). This is cosmetic.

### [FIXED] M6. Product delete loses the image and then fails with an FK violation
- Evidence: `ProductService.php:130-134` deletes the image file first, then `$product->delete()`. `purchase_order_items.product_id` and `stock_transfer_items.product_id` have no cascade (`tenant/..._044...php:13`, `..._042...php:13`), so any product ever on a PO or transfer throws a 500, and the image is already gone.
- Fix: delete the row first, then the file (`DB::transaction(fn() => $product->delete()); $this->deleteImage($product);`), and return a friendly error (or deactivate) when PO or transfer items exist.

### [PARTIAL] M7. Business-type reset silently destroys PO and transfer history and orphans images
- Evidence: `CategoryService.php:39-45` hard-deletes all `purchase_order_items` and `stock_transfer_items` for the tenant's products. The PO and transfer headers keep `total_amount` but lose every line. Product image files are never removed. Held (pending) orders keep items with `product_id = NULL`, so recall and checkout then fail validation (`CheckoutRequest.php:41`).
- Fix: before the reset, block it (or warn) when POs or transfers exist, or set the item `product_id` nullable with `nullOnDelete` plus a stored `product_name` snapshot like `order_items`. Delete held orders in the same transaction. Collect `image_path`s and delete files after commit. The owner check (`SettingsController.php:38`) and the `CHANGE` confirmation are correct.

### [PARTIAL] M8. Reports break for large tenants and miscount refunds
- Evidence: `ReportService.php:80,110` do `->pluck('id')` of every completed order in range and then `whereIn('order_id', $ids)`. The date range is unbounded (`ReportController.php:24-25`). Above 65,535 orders Postgres rejects the statement (bind parameter limit), and below that it is a large memory and network hit. Partially refunded orders stay `Completed` and are summed at full `total` (lines 22-26, 132, 163), so revenue is overstated (`refunded_amount` is ignored).
- Fix: replace the pluck with a subquery, as `getSummary` already does (lines 29-38): `->whereIn('order_id', $this->baseQuery(...)->select('id'))`. Use `SUM(total - refunded_amount)` for revenue. Cap the range (e.g. `before_or_equal` 366 days after `date_from`).

### [FIXED] M9. Missing indexes on Postgres foreign keys used in every report and shift query
- Evidence: Laravel `foreignId()->constrained()` does not create an index on Postgres. No index exists on `order_items.order_id` (`create_order_items_table.php`), `payments.order_id` (`create_payments_table.php`), `orders.shift_id` (`add_shift_id_to_orders_table.php`), `orders.branch_id`, or `inventory_adjustments.inventory_id`.
- Fix: one migration that adds `index('order_id')` on order_items and payments, `index(['tenant_id','branch_id','status','created_at'])` on orders, `index('shift_id')` on orders, and `index('inventory_id')` on inventory_adjustments.

### [FIXED] M10. Promo code race at registration gives a free paid plan
- Evidence: `CreateNewUser.php:49-65` validates the promo, then `RegisterService.php:49-53` re-checks it without a lock. If the promo became invalid (max_uses reached by a concurrent signup), the tenant is still created on the paid plan with `promo_code_id = null`. `incrementUsage()` is unlocked, so `max_uses` can be overshot.
- Fix: in `RegisterService`, `PromoCode::where(...)->lockForUpdate()->first()`. If it is invalid for a paid plan, throw a `ValidationException` (rolls back the transaction) instead of continuing.

### [PARTIAL] M11. Chat send has no throttle and emails two hardcoded personal addresses per message
- Evidence: `routes/tenant.php:352` has no throttle. `ChatService.php:105` does `Mail::to(['rhuzzel.paramio@omnipos.shop', 'boyparamio@gmail.com'])->queue(...)` for every message. Attachments go to the `public` disk (`ChatService.php:76-80`), which is ephemeral on Railway, as are receipt logos (`ReceiptCustomizationController.php:59-62`).
- Fix: add `throttle:20,1` to the chat routes. Move the recipients to `config('mail.support_recipients')` / env. Store attachments on the S3 disk already used for products (`config/filesystems.php:18`), or mount a Railway volume.

### [PARTIAL] M12. Production runs on `php artisan serve`
- Evidence: `railway.toml` `startCommand = "php artisan serve ..."` is the single-threaded PHP dev server. One slow PDF or report blocks every other request for every tenant.
- Fix: use FrankenPHP/Octane or nginx + php-fpm (Railpack supports a PHP-FPM start). At minimum set `PHP_CLI_SERVER_WORKERS=4`.

### [FIXED] M13. Setup endpoints usable by any tenant user
- Evidence: `routes/tenant.php:38-40` have no permission. `SetupController::store` (lines 49-62) creates a branch (plan-limited) and reassigns the caller to it even when branches already exist.
- Fix: `abort_if($tenant->branches()->exists(), 403)` at the top of `store()`, or restrict it to the owner.

---

## Low

- [OPEN] L1. Receipt email endpoint can send to any address with no throttle: `OrderController.php:97-110` (`orders.view`). Add `throttle:10,1`. Note: `SendReceiptEmail` has `tries=3` but no `failed()`. The same applies to the shift mail listeners, which use synchronous `Mail::send` inside queued listeners (fine), and `CloseStaleShifts` closes with `ending_cash => 0` (`CloseStaleShifts.php:31-34`), producing a fake large cash shortage. Pass `null` or `expected_cash`.
- [OPEN] L2. Public receipt route `/receipts/{token}` has no throttle (`web.php:126-128`). The token is 64 random chars, so guessing is not practical. Add `throttle:60,1` anyway.
- [OPEN] L3. `TrackPageVisit` (`TrackPageVisit.php:21-26`) stores raw IP plus user agent synchronously on every public GET, including Railway's healthcheck on `/` (`railway.toml` `healthcheckPath = "/"`). Truncate or hash the IP, or point the healthcheck at `/up`.
- [OPEN] L4. Dashboard (`routes/tenant.php:42`) has no permission, so cashiers see tenant-wide revenue, top products and payment breakdown. Gate the financial blocks on `reports.view`.
- [OPEN] L5. Kitchen poll `since` (`KitchenDisplayController.php:53`) is unvalidated. A malformed value makes Postgres throw a 500. Add `'since' => 'nullable|date'`.
- [OPEN] L6. User import `rows.*.email` is not checked for uniqueness at import time (`UserController.php:252-259`), so concurrent or duplicate rows give a 500 from the unique index. Re-run `validateCsv` server side.
- [OPEN] L7. Email OTP is stored in plaintext and compared with `!==` (`VerifyEmailOtpController.php:36`). Use `hash_equals` and store a hash. Throttle 6/min is present.
- [OPEN] L8. `stancl/tenancy` is installed but unconfigured (no `config/tenancy.php`). Its queue bootstrapper still hooks every job (`event:list`). Remove the package or configure it.
- [UNVERIFIED] L9. Session cookie `secure` is env-driven (`config/session.php:172`). Make sure `SESSION_SECURE_COOKIE=true` and `APP_DEBUG=false` on Railway (`.env*` files could not be read in this review).
- [OPEN] L10. Dependencies: `barryvdh/laravel-dompdf: "*"` and `laravel/socialite: "*"` (`composer.json:13,17`). Locked at dompdf 3.1.6 / socialite 5.31.0 (current, `enable_remote=false`), but `*` allows a future major on `composer update`. Pin `^3.1` and `^5.31`.
- [OPEN] L11. `PosPinController::verify` runs `Hash::check` against every PIN holder in the tenant on every attempt (lines 25-31). That is N bcrypt operations per request, a CPU DoS lever for large tenants (fixed by H2's per-user verify).
- [OPEN] L12. A `roles.edit` holder can add any permission to their own role (`RoleService::update`, `RoleController.php:64-76`). This is acceptable if intended. Otherwise block editing the caller's own role.

---

## Overlaps (pick one source of truth)

- [PARTIAL] O1. Stock movement logic exists in 7 copies: `InventoryService::adjust` (locks), `decrementForSale` (locks), `incrementForVoid` (locks), `RefundService::incrementForRefund` (locks), `PurchaseOrderService::receive` (no lock), `StockTransferService::ship/receive/cancel` (no lock, no negative check), and `ProductService::create/update` initial stock. Source of truth: one `InventoryService::move(Tenant, productId, branchId, int $delta, AdjustmentType, ?ref, userId)` that locks, checks negativity and fires `LowStockReached`. Fixes H4 and part of C4 in one place.
- [PARTIAL] O2. Document number generation exists 5 times (`PosService::generateOrderNumber`, `RefundService::generateRefundNumber`, `PurchaseOrderService::generatePoNumber`, `StockTransferService::generateTransferNumber`, `HeldOrderService::generateHeldNumber`). Make one `DocumentNumber::next($tenant, 'ORD')` helper (M2).
- [OPEN] O3. Sales stats are computed in 6 places with differing rules: `DashboardController.php:53-139` (business logic in the controller), `ReportService`, `AIInsightsService`, `SendEndOfDaySummary`, `SendShiftEndedEmail`, and `ShiftService::getShiftSummary`. They disagree on refunds and day boundaries. Source of truth: `ReportService` (move the dashboard queries into it).
- [OPEN] O4. Promotion discount math is duplicated: `PosService.php:199-217` vs `PromotionService::applyPromoCode` (lines 84-91). Source of truth: a `Promotion::discountFor(float $amount)` method used by both.
- [PARTIAL] O5. Promo code validation for signup exists 3 times: the `web.php:77-102` closure, `CreateNewUser.php:56-65`, and `RegisterService.php:49-53`. Keep the `RegisterService` (locked) check and have the other two call it.
- [OPEN] O6. PIN uniqueness check exists 3 times: `PosPinController::set`, `PosPinController::setForUser`, and `SetupController::storePin` (and is itself the H2 oracle). Delete it, or move it into one `TenantUser::pinTaken()`.
- [OPEN] O7. Owner checks and the owner-permission derivation are duplicated: `(int) user()->id !== (int) owner_id` appears in `UserController` (x3), `PosPinController`, and `SettingsController` (strict, no cast). The "owner gets all permissions" logic is in both `IdentifyTenant.php:56-60` and `PosPinController.php:51-55`. Add `Tenant::isOwner(User)` and `Role::effectivePermissions()`.
- [OPEN] O8. Low-stock definition exists 3 times: `Inventory::scopeLowStock`, `ReportService.php:210`, and the crossing logic in `InventoryService.php:158-164`. Put it on the `Inventory` model (`isLowStock()`).
- [FIXED] O9. Event listener registration happens twice (H5). Rely on discovery only.
- [OPEN] O10. Tenant-scoped `exists` rules are written inline in some FormRequests and missing in others (C4). Add a tiny helper, e.g. `TenantRules::exists('branches')`, and use it everywhere.

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
