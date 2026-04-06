# OmniPOS - Project Tracking

## Current Codebase State (2026-03-27)

### Stack
- Laravel 12 + Inertia.js + Vue 3 (script setup + TypeScript) + Tailwind CSS 4
- Auth: Laravel Fortify + 2FA
- Multi-tenancy: Path-based (`/{tenant}/...`), single shared PostgreSQL DB, scoped by `tenant_id`
- UI: shadcn/reka-ui components in `@/components/ui/`
- Deployment: Railway (production)

### Architecture
- Single DB: All tables in one `omnipos` database; tenant data scoped by `tenant_id` column
- Service layer pattern: Controllers -> Services -> Models
- Tenant model uses string primary key (`$table->string('id')->primary()`)

### Key Directories
- Central migrations: `database/migrations/` (35 files)
- Tenant migrations: `database/migrations/tenant/` (18 files)
- Central models: `app/Models/` (User, Tenant, TenantUser, Plan, TenantSubscription)
- Tenant models: `app/Models/Tenant/` (19 models)
- Services: `app/Services/Tenant/` (12) + `app/Services/Central/` (1)
- Vue pages: `resources/js/pages/tenant/` (48) + `resources/js/pages/admin/` (7)

### Routes
- `routes/web.php` - Welcome, dashboard redirect
- `routes/settings.php` - Profile/security/appearance
- `routes/tenant.php` - All tenant routes (branches, roles, users, products, inventory, POS, orders, etc.)
- `routes/admin.php` - Super admin panel

---

## Implemented Features

### Release Notes Admin CRUD (2026-03-30)
- Full CRUD for release notes at `/admin/release-notes`
- Backend: `ReleaseNoteController`, `ReleaseNote` model, `ReleaseNoteItemType` enum
- Migrations: `create_release_notes_table`, `add_last_seen_release_note_id_to_users_table`
- Pages: `resources/js/pages/admin/release-notes/` (Index, Create, Edit)

### Release Notes Create Page — Templates + Live Preview (2026-03-30)
- Upgraded `Create.vue` to match PromoCode Create pattern (two-column layout)
- **Template picker** (4 cards): Major Release (orange/Rocket), Feature Drop (purple/Sparkles), Hotfix (red/Bug), Custom (gray/SlidersHorizontal)
- `applyTemplate()` pre-fills title, version, summary, items, and publish toggle per template
- **Live preview** sticky right panel showing: title + version badge, summary, items with colored type badges (NEW=teal, FIX=red, IMP=blue), publish status, and "As seen in What's New" mini card mimicking WelcomeBackModal rendering

### Welcome Back Modal with Release Notes (2026-03-30)
- `WelcomeBackModal.vue` shows on dashboard login with today's stats + unread release notes
- Colored badges: NEW (teal), FIX (red), IMP (blue) — shared `typeBadge` map
- Dismiss calls `markReleaseNotesSeen` to update `users.last_seen_release_note_id`

### Bug Fix: markReleaseNotesSeen Inertia Error (2026-03-30)
- `DashboardController::markReleaseNotesSeen()` was returning `response()->json(['ok' => true])` (plain JSON)
- Frontend calls it via `router.post()` (Inertia) which requires Inertia-compatible response
- Fixed: changed return to `return back()` (RedirectResponse)

### Shift Schedule: Date to Weekly Days Refactor (2026-03-30)
- Replaced single `scheduled_date` (date) column with `days_of_week` (JSON array of `["mon","tue",...]`)
- Migration `2025_01_01_000051_convert_shift_schedules_to_weekly.php` in `database/migrations/` (central)
- Converts existing `scheduled_date` data using PostgreSQL `EXTRACT(DOW FROM ...)` to day abbreviations
- Dropped old indexes on `scheduled_date`, added `(tenant_id, user_id)` index
- **Files changed (7):**
  - `database/migrations/2025_01_01_000051_convert_shift_schedules_to_weekly.php` (new)
  - `app/Models/Tenant/ShiftSchedule.php` — fillable/casts: `scheduled_date` -> `days_of_week` (array)
  - `app/Http/Requests/Tenant/ShiftScheduleRequest.php` — validates `days_of_week` as required array, items in `mon-sun`
  - `app/Services/Tenant/ShiftScheduleService.php` — `list()` uses `whereJsonContains` for day filter, orders by `start_time`
  - `app/Http/Controllers/Tenant/ShiftScheduleController.php` — single `day` query param replaces `date_from`/`date_to`
  - `resources/js/types/models.ts` — added `DayOfWeek` type, `ShiftSchedule.days_of_week: DayOfWeek[]`
  - `resources/js/pages/tenant/shift-schedules/Index.vue` — 7 toggle buttons (Mon-Sun) in modal, day badges in table, day dropdown filter

### Email Template Visual Fix (2026-03-31)
Fixed broken logo and width mismatch in email templates (password reset, login alert, verification).
- `resources/views/vendor/mail/html/header.blade.php` — replaced base64 SVG with `icon.png` from app URL
- `resources/views/vendor/mail/html/layout.blade.php` — moved header inside `.inner-body` (570px card)
- `resources/views/vendor/mail/html/themes/default.css` — split border-radius: header top corners, body bottom corners

### Mandatory Branch Assignment for Users (2026-04-01)
Removed "All Branches" option from user management. Every user must now be assigned to a specific branch.
- `resources/js/pages/tenant/users/Index.vue` — Removed "All Branches" `SelectItem` from Add/Edit dialogs, default to newest branch via `newestBranchId` computed
- `app/Http/Controllers/Tenant/UserController.php` — `store()` and `update()` fallback to newest active branch when `branch_id` is null
- `app/Http/Controllers/Tenant/SetupController.php` — `store()` now assigns owner to the newly created branch
- `database/migrations/2026_03_31_230000_assign_null_branch_users_to_newest_branch.php` — One-time migration to fix existing users with null branch_id
- **Files changed (4):** `Index.vue`, `UserController.php`, `SetupController.php`, new migration

### Auto-Initialize Inventory on Branch Creation (2026-04-01)
When a branch is created, inventory records (quantity=0) are auto-created for all existing products so they appear on the Inventory page immediately.
- `app/Services/Tenant/BranchService.php` — `create()` bulk-inserts inventory records for all tenant products after branch creation
- **Files changed (1):** `BranchService.php`

### Food/Non-Food Product Toggle + Initial Stock (2026-04-04)
Added `is_food` boolean to products for distinguishing food (made to order, no stock tracking) from non-food (physical goods, require stock count). Initial stock input on product creation for non-food items.
- `database/migrations/tenant/2025_01_01_000051_add_is_food_to_products_table.php` (new) — `is_food` boolean, default `false`
- `app/Models/Tenant/Product.php` — Added `is_food` to `$fillable` + `casts`
- `resources/js/types/models.ts` — Added `is_food: boolean` to `Product` interface
- `app/Http/Requests/Tenant/ProductRequest.php` — `is_food` + `initial_stock` validation, `withValidator()` conditional requirement (required on POST for non-food)
- `app/Services/Tenant/ProductService.php` — Injected `InventoryService`, creates `Inventory` record + `AdjustmentType::Initial` adjustment for non-food on creation
- `app/Http/Controllers/Tenant/ProductController.php` — Extracts `initial_stock` + `branchId`, passes to service
- `resources/js/pages/tenant/products/Create.vue` — Switch toggle (Non-Food/Food) + conditional initial stock input
- `resources/js/pages/tenant/products/Edit.vue` — Switch toggle (no initial stock field — managed via Inventory module)
- `app/Services/Tenant/InventoryService.php` — `decrementForSale()` + `incrementForVoid()` skip food products
- **Backward compatible:** All existing products default to non-food (no behavior change). Users manually toggle food products via Edit page.
- **Files changed (9):** 1 new, 8 modified

### Tenant User Activity Tracking in Admin Panel (2026-04-04)
Per-tenant "User Activity" page at `/admin/tenants/{id}/activity` for admins to monitor tenant user behavior. No new migrations — aggregates from existing tables.
- `app/Services/Central/TenantActivityService.php` (new) — `getSummaryStats()`, `getTimeline()`, `getTenantUsers()` merging 6 data sources (user_logins, activity_logs, shifts open/close, orders, products)
- `app/Http/Controllers/SuperAdmin/TenantController.php` — Added `activity()` method, injected `TenantActivityService`
- `routes/admin.php` — Added `GET tenants/{id}/activity` route
- `resources/js/pages/admin/tenants/Activity.vue` (new) — Stat cards, filter bar (user/event type/date range), timeline table with colored badges, expandable properties, pagination
- `resources/js/types/models.ts` — Added `TenantActivityEvent`, `TenantActivityStats` interfaces
- `resources/js/pages/admin/tenants/Show.vue` — Added "User Activity" button in actions area
- **Files changed (6):** 2 new, 4 modified

### Landing Page SEO Optimization (2026-03-31)
Full SEO overhaul of `Welcome.vue` landing page and supporting files. Domain: `omnipos.shop`.

**Welcome.vue (`resources/js/pages/Welcome.vue`):**
- Title: `"OmniPOS — All-in-One POS System for Philippine Businesses | Free POS Software"`
- Meta description (155 chars), meta keywords (10 PH-focused terms), meta author, meta robots
- Canonical URL: `https://omnipos.shop/`
- Full Open Graph tags: og:type, og:url, og:title, og:description, og:image, og:site_name, og:locale (en_PH)
- Full Twitter Card tags: summary_large_image with title, description, image
- PWA meta tags: theme-color (#0d9488), application-name, apple-mobile-web-app-*
- JSON-LD structured data injected via `onMounted`: `SoftwareApplication` schema (features, pricing, ratings) + `Organization` schema
- Semantic HTML: `aria-label` on nav, logo converted to `<a href="/">`, `aria-hidden` on decorative SVGs, `role="contentinfo"` on footer, `aria-label` on hero section

**Blade template (`resources/views/app.blade.php`):**
- Added `theme-color`, default `meta description`, `X-UA-Compatible` meta tags
- Added `dns-prefetch` for Bunny fonts
- Changed title fallback from `'Laravel'` to `'OmniPOS'`
- Added `<noscript>` fallback to make `data-animate` content visible without JS

**New/updated files:**
- `public/robots.txt` — Allow `/`, disallow auth/settings pages, sitemap reference
- `public/sitemap.xml` — Homepage (priority 1.0, weekly) + About page (priority 0.8, monthly)
- `public/favicon.svg` — Replaced Laravel logo with branded TrendingUp icon on teal rounded square
- `public/og-image.svg` — OG image source (teal gradient, logo, tagline, POS mockup)
- `public/og-image-preview.html` — HTML preview page with html2canvas PNG download

**Pending:** Download `og-image.png` from preview page and place in `public/`

---

## Issues Found & Changes Tracked

### ISSUE #1: Tenant Migrations Never Run in Production (2026-03-27)
**Status:** Identified, NOT yet fixed
**Severity:** CRITICAL - Causes 500 errors on multiple pages

**Problem:**
Laravel's `php artisan migrate` only runs migrations in `database/migrations/` (root). It does NOT look in subdirectories. The 18 migration files in `database/migrations/tenant/` are never executed on Railway production. There is no custom artisan command or config anywhere in the codebase to run them.

**Affected pages (all return 500):**
- Suppliers - missing `suppliers`, `product_supplier` tables
- Stock Transfers - missing `stock_transfers`, `stock_transfer_items` tables
- Purchase Orders - missing `purchase_orders`, `purchase_order_items` tables
- POS - missing `tables`, `promotions`, `variation_groups`, `variation_options`, `addons`, `product_addon`, `order_item_variations`, `order_item_addons` tables

**Also missing:** 4 ALTER TABLE migrations that add columns to `orders` table:
- `table_id` (from 000046)
- `promotion_id` (from 000048)
- `kitchen_status` + `kitchen_sent_at` (from 000049)
- void fields (from 000050)

**Tables in `database/migrations/tenant/` (18 files):**
1. `2025_01_01_000033_create_suppliers_table.php`
2. `2025_01_01_000034_create_product_supplier_table.php`
3. `2025_01_01_000035_create_variation_groups_table.php`
4. `2025_01_01_000036_create_variation_options_table.php`
5. `2025_01_01_000037_create_addons_table.php`
6. `2025_01_01_000038_create_product_addon_table.php`
7. `2025_01_01_000039_create_order_item_variations_table.php`
8. `2025_01_01_000040_create_order_item_addons_table.php`
9. `2025_01_01_000041_create_stock_transfers_table.php`
10. `2025_01_01_000042_create_stock_transfer_items_table.php`
11. `2025_01_01_000043_create_purchase_orders_table.php`
12. `2025_01_01_000044_create_purchase_order_items_table.php`
13. `2025_01_01_000045_create_tables_table.php`
14. `2025_01_01_000046_add_table_id_to_orders_table.php`
15. `2025_01_01_000047_create_promotions_table.php`
16. `2025_01_01_000048_add_promotion_id_to_orders_table.php`
17. `2025_01_01_000049_add_kitchen_status_to_orders_table.php`
18. `2025_01_01_000050_add_void_fields_to_orders_table.php`

**Proposed fix options:**
- Option A (permanent): Move all 18 tenant migrations into `database/migrations/` root so `php artisan migrate` picks them up automatically
- Option B (quick): Run `php artisan migrate --path=database/migrations/tenant` on Railway (band-aid, must remember every deploy)

**Fix chosen:** TBD
