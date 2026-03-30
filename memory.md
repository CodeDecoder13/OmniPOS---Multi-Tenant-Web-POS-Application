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
