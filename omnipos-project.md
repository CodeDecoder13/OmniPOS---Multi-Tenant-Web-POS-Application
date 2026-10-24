# OmniPOS Project Guide

A plain-language reference for what OmniPOS is, how it is built, and everything it can do today. It is written for new teammates, investors, and support staff. It is based on a scan of the code in `myapp/` (October 2026). Where older docs and the code disagree, this guide follows the code and says so.

---

## 1. What OmniPOS is

OmniPOS is a multi-tenant, web-based point of sale (POS) and back-office system sold as SaaS. It targets small and medium Philippine businesses: cafes, restaurants, milk tea shops, sari-sari and grocery stores, pharmacies, salons, barbershops, laundry shops, hardware stores, and similar. Prices on the marketing site are in Philippine pesos, the default currency is PHP, the Terms of Use are governed by Philippine law, and the POS ships with Senior Citizen, PWD, and Student discount presets.

Each business that signs up becomes a **tenant** with its own workspace at `omnipos.shop/{business-slug}/...`. A tenant can run one or more **branches**, each with its own staff, menu availability, prices, stock, tables, and feature switches.

There are three audiences, each with its own part of the app:

| Audience | Where | What they do |
| --- | --- | --- |
| Business owners and staff | Tenant app, `/{tenant}/...` | Sell at the POS, run the kitchen display, manage products, stock, staff, shifts, customers, promotions, and read reports |
| OmniPOS super admins | Admin panel, `/admin/...` (separate login) | Manage tenants, plans, promo codes, subscriptions, platform users, release notes, support chat, and platform reports |
| Public visitors | Marketing site, `/`, `/features`, `/pricing`, etc. | Learn about the product, compare plans, register |

There is also a small public page for customers: a shareable digital receipt at `/receipts/{token}`.

---

## 2. Tech stack and libraries

Versions are taken from `composer.json` / `package.json` (constraint) and the lock files (installed version).

### Backend (PHP)

| Library | Constraint / installed | Used for |
| --- | --- | --- |
| PHP | ^8.3 (CI tests on 8.4 and 8.5) | Runtime |
| laravel/framework | ^12.0 / 12.70.1 | Application framework (routing, Eloquent ORM, queues, mail, notifications, events, scheduler) |
| inertiajs/inertia-laravel | ^2.0 / 2.0.28 | Server side of Inertia: controllers return Vue pages with props instead of JSON APIs |
| laravel/fortify | ^1.30 / 1.41.0 | Headless auth: login, registration, password reset, email verification, two-factor authentication |
| laravel/socialite | * / 5.31.0 | Google sign-in and Google-based registration |
| laravel/wayfinder | ^0.1.9 / 0.1.23 | Generates typed TypeScript route and controller helpers (`resources/js/routes`, `resources/js/actions`) |
| barryvdh/laravel-dompdf | * / 3.1.2 (dompdf 3.1.6) | PDF generation: thermal receipts, kitchen order tickets (KOT), report exports |
| league/flysystem-aws-s3-v3 | ^3.0 / 3.35.3 (aws-sdk-php 3.402.0) | S3-compatible storage for product images in production |
| stancl/tenancy | ^3.9 / 3.10.1 | Installed but not used. There is no `config/tenancy.php` and no app code references it. Tenancy is custom (see section 3) |
| laravel/tinker | ^2.10.1 | Developer REPL |

Dev tooling: laravel/pint 1.32.1 (PHP code style), laravel/pail 1.2.7 (log tailing), laravel/sail 1.69.0, mockery 1.6.15, fakerphp/faker, nunomaduro/collision, phpunit/phpunit 11.5.57.

### Frontend (TypeScript / Vue)

| Library | Constraint / installed | Used for |
| --- | --- | --- |
| vue | ^3.5.13 / 3.5.30 | UI framework (Composition API, `<script setup>`, TypeScript) |
| @inertiajs/vue3 | ^2.3.7 / 2.3.18 | Client side of Inertia: page routing, forms, partial reloads |
| tailwindcss + @tailwindcss/vite | ^4.1 / 4.2.1 | Styling |
| reka-ui | ^2.9.2 | Headless UI primitives behind the shadcn-vue components in `components/ui` (dialog, sheet, select, tabs, tooltip, sidebar, dropdown, etc.) |
| class-variance-authority, clsx, tailwind-merge, tw-animate-css | various | Component variants and class merging used by the shadcn-vue kit |
| lucide-vue-next | ^0.468.0 | Icon set used everywhere |
| apexcharts + vue3-apexcharts | 5.10.4 / 1.11.1 | Charts on the dashboards, reports, and AI Insights |
| vue-i18n | 12.0.0-alpha.3 | Translations (English, Filipino, Japanese) |
| jsbarcode | 3.12.3 | Rendering product barcode labels |
| vue-input-otp | 0.3.2 | OTP / 2FA code inputs |
| @vueuse/core | 12.8.2 | Composition utilities (event listeners, keyboard shortcuts) |

Build and dev tooling: Vite ^7 (7.3.1), laravel-vite-plugin 2.1.0, @vitejs/plugin-vue, @laravel/vite-plugin-wayfinder, TypeScript 5.9.3, vue-tsc, ESLint 9 (with vue, import, typescript, stylistic plugins), Prettier 3 with the Tailwind plugin. SSR build support exists (`resources/js/ssr.ts`), but nothing in the deploy config starts an SSR server.

### Infrastructure and services

| Concern | What the code uses |
| --- | --- |
| Database | PostgreSQL in production (memory.md says so, and AI Insights uses PostgreSQL-only SQL such as `date_trunc` and `EXTRACT(...)::int`). The framework default in config is SQLite, and tests run on in-memory SQLite |
| Sessions | `database` driver by default |
| Cache | `database` store by default (used for public landing stats and the 24-hour low-stock email debounce) |
| Queue | `database` driver by default. Many mails, notifications, and listeners are queued |
| Mail | Laravel Mail, default mailer `log` in config. Postmark, Resend and SES keys are wired in `config/services.php`; which one production uses is not visible in the repo |
| File storage | `PRODUCT_STORAGE_DISK` selects the disk for product images; production uses an S3-compatible bucket (path-style endpoint). Receipt logos and chat attachments go to the `public` disk |
| Auth providers | Email and password (Fortify), Google OAuth (Socialite), TOTP 2FA (Fortify) |
| Hosting | Railway. The app is built with Railpack and runs the Laravel web server and a looping queue worker in the same service; migrations run before each deploy |
| CI | Two GitHub Actions workflows (lint, tests) exist in `myapp/.github/workflows`. See section 10 for a caveat |

---

## 3. Architecture overview

### Multi-tenancy model

- **One shared database.** All tenants live in the same PostgreSQL database. Every tenant-owned table has a `tenant_id` column. `stancl/tenancy` is installed but not used.
- **Tenant identification by URL path.** All tenant routes are grouped under `/{tenant}` where `{tenant}` is the tenant's slug (generated from the store name, with `-1`, `-2` suffixes if taken). The `IdentifyTenant` middleware looks up the slug, rejects unknown slugs (404), shows a full-screen "Account Deactivated" page if the tenant is inactive, checks that the logged-in user belongs to the tenant and is active, and stores the tenant, membership, role, and permissions on the request.
- **Explicit scoping in services.** There are no global Eloquent scopes. Each service queries with `forTenant($tenant)` or `where('tenant_id', ...)`, and validation rules use tenant-scoped `exists` checks for the most sensitive inputs (products, customers, tables, promotions at checkout).
- **Tenant primary key** is a UUID string. Users are global (one login can belong to several tenants) and are linked through `tenant_users`, which holds the role, the branch, the POS PIN hash, `is_active`, and `last_login_at`.
- **Branches.** Each tenant user is assigned to exactly one branch (mandatory since April 2026). The POS, orders list, kitchen display, and branch-feature checks use the user's branch. Branch-level data includes inventory, branch product prices and availability, tables, shifts, purchase orders, and stock transfers.

### Per-tenant roles and permissions

- Permissions are a global list (41 permissions in 14 groups, seeded by `PermissionSeeder`).
- Roles are per tenant. Four system roles are created for each new tenant (Owner, Admin, Cashier, Staff). Owners can add custom roles with any mix of permissions.
- The Owner system role always receives every permission, even if its pivot rows are changed.
- Enforcement happens in route middleware `can-do:{permission}`. Two more middleware gate features: `branch-feature:{toggle}` (the user's branch must have the feature switched on) and `check-plan:{plans}` (the tenant's plan must be in the list). The frontend hides or locks menu items with the same rules via `useTenantNav`.

### Request flow

1. Browser requests `/{tenant}/orders`.
2. Middleware stack: `web` (session, CSRF, cookies), `HandleAppearance` (theme cookie), `HandleInertiaRequests` (shared props), `auth`, `verified`, `tenant` (`IdentifyTenant`), then `can-do`, `branch-feature`, `check-plan` as needed.
3. A controller in `app/Http/Controllers/Tenant` validates input (often through a Form Request in `app/Http/Requests/Tenant`) and calls a service in `app/Services/Tenant`.
4. The service does the work inside DB transactions where needed and may dispatch domain events (`OrderCompleted`, `LowStockReached`, `ShiftClosed`, etc.).
5. The controller returns `Inertia::render('tenant/orders/Index', props)` for page views, a redirect with a flash message for form posts, or JSON for POS and polling endpoints.

### Services layer

Controllers stay thin; business logic lives in services. There are 27 tenant services (POS, held orders, refunds, inventory, purchase orders, stock transfers, shifts, kitchen display, KOT, receipts, reports, report export, AI insights, roles, users import, branches, branch menu, products, categories, add-ons, customers, promotions, suppliers, tables, shift schedules, activity log, orders) and 11 central services for the admin side (tenants, users, admins, plans, subscriptions, reports, dashboard, chat, registration, tenant activity, admin activity log). `PlanLimitService` enforces plan limits.

### Shared Inertia props

Every page receives: the authenticated user, flash messages, sidebar state, and, inside a tenant, the tenant (name, slug, settings stored in `tenants.data`, subscription status, plan limits), the user's role, the user's permission slugs, the active plans list, and the effective feature settings of the user's branch.

### Internationalization

`vue-i18n` with three locale files: English (`en.json`), Filipino (`fil.json`), Japanese (`ja.json`), each with 243 keys in sections `common`, `nav`, `settings`, `pos`, `receipt`, `dashboard`, `products`, `orders`, `inventory`, `team`. The chosen locale is kept in local storage, and a tenant default can be set in Settings. Coverage is partial: translations are used in the sidebar, command palette, header, dashboard, POS, products, orders, inventory, and settings pages. Most other pages are hardcoded English. Server messages and emails are English only.

### Theming and dark mode

Light, dark, and system themes are supported. The choice is stored in an `appearance` cookie (so the server renders the right theme on first paint) and the tenant can set a default theme. The brand accent is teal. UI components come from the shadcn-vue kit on reka-ui.

### Real-time behaviour

There are no WebSockets. Live features poll:

| Feature | Poll interval |
| --- | --- |
| Kitchen display | every 5 seconds (with a two-tone audio alert for new orders, mutable) |
| Notification bell | every 30 seconds |
| OmniChat (tenant side) | every 3 seconds while open, every 30 seconds when minimized |
| Admin chat inbox | messages every 3 seconds, conversation list every 10 seconds |

### Events, queue, and scheduler

- Events and listeners: `OrderCompleted` (log activity, auto-email receipt), `OrderVoided` and `OrderRefunded` (log activity), `LowStockReached` (in-app notification, queued email, queued auto-reorder PO), `ShiftOpened` and `ShiftClosed` (queued emails to the owner), Laravel `Login` (record login, send login alert). `OrderHeld` and `PaymentReceived` events exist; `OrderHeld` is dispatched but has no listener, `PaymentReceived` is never dispatched.
- Queue: database queue, worker started alongside the web server on Railway.
- Scheduler (`routes/console.php`): `shifts:close-stale` hourly, `reports:daily-summary` daily at 23:30.
- Other console command: `products:migrate-images` (moves product images from the public disk to the configured product disk, with a dry-run option).

---

## 4. Feature catalogue (tenant app)

Permissions are shown as slugs (for example `pos.access`). "Owner only" means the code checks that the user is the tenant owner, regardless of role.

### 4.1 Registration and onboarding

- **Sign-up wizard** (`/register`), four steps: (1) name and email, (2) store name and business type, (3) plan and promo code, (4) password. Google sign-ups skip the password step.
- **Google sign-in.** If the Google email matches an existing user, the account is linked, the email is marked verified, and the user is logged in (or sent to the 2FA challenge if 2FA is on). Otherwise the Google profile is held in the session and the user finishes the registration wizard.
- **Paid plans need a promo code.** There is no payment gateway. Choosing Pro or Enterprise requires a valid promo code for that plan; the code is validated live (`/promo-codes/validate`, throttled) and on submit, and its use count is incremented.
- **What registration creates** (in one transaction): the tenant with a unique slug, the four system roles, the owner membership, an `active` subscription on the chosen plan (with the promo code recorded), the default categories for the business type, and three preset ID discounts (Student, PWD, Senior Citizen, 20% each). A queued welcome email is sent.
- **Email verification** uses a 6-digit code (valid 15 minutes) instead of a link. Google sign-ups are verified automatically.
- **Setup wizard** (`/{tenant}/setup`), shown until done: step 1 creates the first branch (the owner is assigned to it, and inventory rows are created for any existing products); step 2 sets the owner's 4-6 digit POS PIN. The dashboard redirects to setup while the tenant has no branches.
- **Welcome back modal** on the dashboard after login: today's stats plus unread release notes, with NEW / FIX / IMP badges. Dismissing it marks the notes as seen.

### 4.2 Plans and limits

Seeded by `PlanSeeder` (admins can edit plans later):

| Plan | Price (PHP / month) | Branches | Users | Products | Feature list shown to customers |
| --- | --- | --- | --- | --- | --- |
| Free | 0 | 1 | 3 | 100 | Single branch, up to 3 users, up to 100 products, basic POS, daily reports, priority support |
| Pro | 1,499 | 5 | 25 | 5,000 | Split payments, hold and recall, full and partial refunds, advanced reports, EOD summary emails, inventory, low stock notifications, customer history, digital receipts, priority support |
| Enterprise | 3,999 | Unlimited | Unlimited | Unlimited | Everything, real-time analytics and EOD reports, auto-reorder, custom receipt branding, in-app and email low stock alerts, priority support, API access |

What the code actually enforces:

- **Numeric limits** on creating branches, users (including CSV import), and products. Hitting a limit shows "Your {plan} plan allows max N ...".
- **Subscription status:** creating those resources is blocked when the subscription is Cancelled, Expired, or Past Due.
- **AI Insights** requires Pro or Enterprise (route middleware).
- **Receipt Design** (receipt customization) requires Enterprise (controller check and locked UI).
- Other items in the plan feature lists (split payments, refunds, hold orders, EOD emails, low stock alerts, digital receipts) are available on every plan; they are not gated in code. "API access" is listed for Enterprise but there is no public API.

Plan changes: Settings has a Plan & Billing card showing the current plan and its limits. It states that online payment is coming soon and offers a "Request a plan change" email link to `support@omnipos.shop`. Plan changes are done by a super admin.

### 4.3 Subscriptions and promo codes

- One subscription per tenant (`tenant_subscriptions`) with plan, status, optional promo code, `trial_ends_at`, `ends_at`.
- Statuses: Active, Trial, Cancelled, Expired, Past Due, Pending. New sign-ups always start as Active; trials only exist if an admin sets them.
- Promo codes (platform level, managed by super admins): code, description, percentage or fixed discount, maximum uses, used count, valid from and until, active flag, and an optional list of plans the code applies to. In practice they act as the access key for paid plans; no money is charged in-app.

### 4.4 POS and checkout

Route: `/{tenant}/pos`, permission `pos.access`, branch toggle `pos_enabled`. Opens in its own full-screen POS layout (new tab).

- **Product grid** with search (name or SKU), category filter, branch prices (branch price overrides the global price), and only products available at the user's branch. Products can have variation groups (for example Size, with price modifiers) and add-ons.
- **Cart** with quantities, per-item notes (for example "no onions", shown on KOT, kitchen display, and receipt), order-level notes, order type (Dine In or Take Out), optional table, optional customer (search or quick-add from the POS).
- **Barcode scanning.** A USB scanner is detected by keystroke speed (under 50 ms between keys); the scanned code is matched against product SKU, with a success or error beep. There is also a manual barcode field. Barcode labels can be printed from products using JsBarcode.
- **Keyboard shortcuts:** F2 focuses product search, F4 holds the order, F9 goes to payment. Disabled while a dialog is open.
- **Discounts:**
  - Manual discount, percentage (max 100%) or fixed, shown only if the operator has `pos.discount` and the branch has `discounts_enabled`.
  - Promo codes (tenant promotions) applied by code, with validity checks (active, date window, usage limit, minimum order amount, maximum discount cap).
  - **ID discount panel** for preset Student, PWD, and Senior Citizen discounts. It records the customer's name, ID number, and birthday on the order; Senior Citizen requires age 60 or above (checked in the browser).
- **Tax:** tenant-wide tax rate and label, inclusive or exclusive. Inclusive tax is backed out of the total; exclusive tax is added on top.
- **Payments:** Cash (with amount tendered and change), Card, and E-Wallet in the POS UI, each with an optional reference number. "Online" is shown disabled with a "Soon" badge. The data model also supports Bank Transfer and Other. **Split payments**: several payment rows per order, which must add up to the total.
- **Shifts are required.** Checkout fails if the operator has no open shift. The cashier opens a shift with starting cash and closes it with counted cash (see 4.15).
- **PIN login and Switch User.** Several cashiers can share one logged-in device. "Switch User" locks the till; the next cashier enters their 4-6 digit PIN, which is verified on the server (throttled to 5 attempts per minute) and stored in the session for 8 hours. Orders are attributed to the PIN-verified operator and use that operator's open shift.
- **Hold and recall (park orders).** A cart can be saved as a held order (number `HLD-YYYYMMDD-NNNN`, no payment, no stock change), listed in a drawer, recalled into the cart, or deleted. Completing a recalled order replaces the held record.
- **Checkout effects** (one transaction): order number `ORD-YYYYMMDD-NNNN`, price snapshot of each item with variations and add-ons, promotion usage count incremented, table marked Occupied, order sent to the kitchen if the branch has `kitchen_display` on, stock decremented for non-food items (fails with "Insufficient stock" if not enough), payments recorded, a random 64-character receipt token created, and the `OrderCompleted` event fired.
- **Receipts:** printable thermal receipt in the browser (58 mm or 80 mm width setting), PDF receipt, email receipt (queued, with a "view online" link), and a copyable public link. If the order has a customer with an email address, the receipt is emailed automatically.
- **Billing history** panel in the POS shows recent orders for reprint.
- **KOT printing** from the POS and the kitchen display.

### 4.5 Orders, voids, and refunds

- **Orders list and detail** (`orders.view`), filtered by search, status, and date. Users only see orders from their own branch.
- **Void** (`pos.void`): only Completed orders, reason required. Sets status Voided, records who and when, returns stock (adjustment type Return), frees the table if no other active order uses it, and gives back one use of the promotion.
- **Refund** (`pos.void`): full or partial, reason required. Partial refunds select items and quantities (refund amount = item unit price x quantity). Refund number `REF-YYYYMMDD-NNNN`. A negative payment row is recorded with the original payment method, `refunded_amount` is updated, the order becomes Refunded once fully refunded, and stock is returned for non-food items (adjustment type Refund).
- Order statuses: Pending (used for held orders), Completed, Voided, Refunded.

### 4.6 Kitchen display (KDS) and KOT

Route `/{tenant}/kitchen`, permission `kitchen.access`, branch toggle `kitchen_display`. Opens in a new tab.

- Shows the branch's kitchen orders as cards sorted New, then Preparing, then Ready, oldest first, with items, variations, add-ons, item notes, table, and cashier.
- Kitchen statuses: New, Preparing, Ready (records completion time), Served.
- Polls every 5 seconds and plays an audio alert for new orders; sound can be muted.
- KOT (Kitchen Order Ticket) can be printed from the browser or downloaded as a PDF.

### 4.7 Tables and dine-in

Permissions `tables.view/create/edit/delete`; the menu item is shown only when the branch has `dine_in` on. Tables have a name, branch, capacity (1-100), sort order, active flag, and status: Available, Occupied, Reserved, Maintenance. Checkout marks a table Occupied; voiding frees it.

### 4.8 Products, categories, variations, add-ons

- **Products** (`products.*`): name, slug, SKU (unique per tenant, used as the barcode), description, image (jpg, png, webp up to 2 MB), price, cost price (cannot exceed price), active flag, category, and a Food / Non-Food switch. Food items (made to order) skip stock tracking; non-food items take an initial stock quantity on creation.
- **Variation groups** per product, each with options and price modifiers, optionally required.
- **Add-ons**: tenant-wide extras with price, label, sort order, active flag, linked to products.
- **Branch availability**: a product can be available at all branches (default) or only selected branches.
- **Categories**: name, slug, description, sort order, active flag, and inline creation from the product form. New tenants receive default categories for their business type (section 9).
- Product images are served through an authenticated tenant route from the configured disk (S3 in production).
- Plan limit applies to product count.

### 4.9 Branch menu, pricing, and feature toggles

- **Branches** (`branches.*`): name, code (printed on receipts), address, phone, email, active flag. Creating a branch counts against the plan and creates zero-stock inventory rows for every product. A branch cannot be deleted if it has orders, shifts, transfers, purchase orders, or assigned users.
- **Branch Menu & Pricing** (`branches.edit`): per product, set available or not at that branch and an optional custom price. Effective price = branch price if set, otherwise the global price.
- **Branch Feature Settings** (`branches.edit`): nine switches stored per branch.

| Toggle | Default | What it controls in code |
| --- | --- | --- |
| `pos_enabled` | on | POS routes and checkout |
| `inventory_tracking` | on | Inventory, Purchase Orders, Stock Transfers, Suppliers menu and routes |
| `kitchen_display` | on | Kitchen routes; whether new orders are sent to the kitchen |
| `discounts_enabled` | on | Manual discounts in POS and at checkout |
| `dine_in` | on | Tables menu item |
| `takeout` | on | Stored and shown, effect in POS not verified |
| `receipt_printing` | on | Stored and shown, effect not verified |
| `customer_loyalty` | off | Not used by any feature (loyalty is planned) |
| `delivery` | off | Not used by any feature |

Disabled features appear dimmed in the sidebar with a hint instead of disappearing.

### 4.10 Inventory

Route `/{tenant}/inventory`, permissions `inventory.view` and `inventory.manage`, branch toggle `inventory_tracking`.

- One inventory row per product per branch (non-food products only): quantity on hand, low stock threshold, reorder point, reorder quantity.
- Stat cards (total, low stock, in stock) are computed on the server across all pages; the low-stock card filters the list.
- **Manual adjustment types:** Purchase, Damage, Correction, Initial, Transfer Out, Transfer In, Refund (Sale and Return are system-only). Adjustments cannot take stock below zero. Thresholds and reorder fields can be saved with or without a quantity change.
- **History**: every change is an `inventory_adjustments` row with before, after, change, reason, reference (order), and the user.
- **Low stock alerts:** when a sale takes stock across the low stock threshold or the reorder point (downward only), `LowStockReached` fires: an in-app notification to the owner and to branch users with the Admin role (or a role with slug `manager`), and a queued email to the same people, at most once per item per 24 hours.
- **Auto-reorder:** if reorder point and reorder quantity are set, there is no open (Draft or Sent) PO for that product at that branch, and the product has a linked supplier (preferred first), a Draft purchase order is created automatically.

### 4.11 Purchase orders

Permissions `inventory.view` / `inventory.manage`, branch toggle `inventory_tracking`. PO number `PO-YYYYMMDD-NNNN`. A PO has a supplier, branch, expected date, notes, items (quantity ordered, unit cost, subtotal), and total. Lifecycle: Draft, then Sent, then Partial or Received, or Cancelled. Receiving records quantities per item, adds stock (adjustment type Purchase), and marks the PO Partial until everything has arrived.

### 4.12 Stock transfers

Same permissions and toggle. Transfer number `TRF-YYYYMMDD-NNNN`, source branch, destination branch, items. Lifecycle: Pending, then In Transit (on ship: stock leaves the source, Transfer Out), then Completed (on receive: stock arrives at the destination, Transfer In), or Cancelled. Quantities requested, sent, and received are tracked separately.

### 4.13 Suppliers

`suppliers.*`. Name, contact person, email, phone, address, notes, active flag. Products link to suppliers with a supplier cost price, supplier SKU, and a preferred flag (used by auto-reorder).

### 4.14 Customers

Viewing needs `orders.view`; creating, editing, and deleting need `orders.manage`. Fields: name, email, phone, address, notes, active flag. Customer detail page shows total orders, total spent, average order value, last visit, and paginated order history. Customers with orders cannot be deleted. Customers can be searched and added from the POS.

### 4.15 Shifts and scheduling

- **Shifts** (POS opens and closes them; `shifts.view` to browse): starting cash, opened time. On close: counted cash, expected cash (starting cash plus completed cash payments), difference (counted minus expected; negative means short), total sales, total orders, hours rendered, notes. A user can only have one open shift.
- Shift start and shift end emails go to the owner (end email includes sales, orders, voids, average order value, payment breakdown, and products sold).
- **Auto-close:** shifts open longer than 12 hours are closed hourly by the scheduler with ending cash 0 and an "Auto-closed" note.
- **Shift schedules** (gated by `users.edit-role`): per user and branch, a weekly pattern of days (Mon to Sun) with start and end time and notes. Filterable by day.

### 4.16 Promotions

`promotions.*`. Fields: code (unique per tenant), name, type, value, minimum order amount, maximum discount cap, start and end dates, active flag, usage limit, used count, description. Types: Percentage, Fixed Amount, Buy X Get Y, Student, PWD, Senior Citizen (the last three are presets created at signup). Each promotion has a usage page.

### 4.17 Reports and analytics

Route `/{tenant}/reports`, permission `reports.view`. Filters: date range (default last 30 days), period (daily, weekly, monthly), branch.

| Report | Contents |
| --- | --- |
| Summary | Headline totals for the range (revenue, orders, average order value, and related cards) |
| Sales trend | Revenue and orders over time by period |
| Top products | Best sellers by quantity and revenue |
| Payment breakdown | Totals by payment method |
| Operator performance | Sales per cashier |
| Branch comparison | Sales per branch |
| Inventory report | Stock levels and movement in the range |
| Tax report | Tax collected by period |
| Forecast | Projection from recent daily averages |

Export: each report type (sales summary, products, payments, operators, branches, inventory, tax) can be downloaded as CSV or PDF (A4).

The tenant **dashboard** shows today's revenue against yesterday, recent orders, a 7-day revenue chart, top products, quick actions filtered by permission, plan usage against limits, and counts of branches, users, roles, categories, and products.

### 4.18 AI Insights

Route `/{tenant}/ai-insights`, permission `reports.view`, Pro or Enterprise only. Despite the name, there is no machine learning or external AI service; it is rule-based statistics on the tenant's own sales:

- Month-over-month change in revenue, orders, and average order value, plus a projected 7-day revenue from the last 14 days.
- 30-day revenue forecast from a linear trend over the last 90 days, with 7, 14, and 30-day projected totals, 7 and 30-day moving averages, and an average-by-weekday pattern.
- Peak hours, product trends (current vs previous period).
- Generated insight cards: Revenue Growing / Declining / Stable, First Sales, Best Sales Day, Peak Business Hour, Trending Product, Declining Product, Order Volume Up / Down, Not Enough Data.

### 4.19 Notifications and emails

All emails use OmniPOS teal branding. "Queued" means sent by the background worker.

| Class | Type | When it is sent | Recipient |
| --- | --- | --- | --- |
| `WelcomeNotification` | Mail, queued | After registration | New owner |
| `VerifyEmailOtpNotification` | Mail, queued | When email verification is requested | User |
| `LoginAlertNotification` | Mail, queued | Every web login (with IP and browser) | User |
| Password reset (Laravel) | Mail | Forgot password | User |
| `LowStockNotification` | Database (bell) | Stock crosses threshold or reorder point | Owner and branch Admin/manager users |
| `LowStockEmailNotification` | Mail, queued | Same trigger, max once per item per 24 h | Same |
| `EndOfDaySummaryNotification` | Mail, queued | Daily 23:30 for active tenants with sales that day | Owner |
| `ReceiptMail` (via `SendReceiptEmail` job, 3 tries) | Mail, queued | Manual "Email receipt", or automatically when the order's customer has an email | Customer |
| `ShiftStartedMail` | Mail (listener queued) | Shift opened | Owner |
| `ShiftEndedMail` | Mail (listener queued) | Shift closed | Owner |
| `TenantDeactivatedMail` | Mail, queued | Super admin deactivates the tenant | Owner |
| `TenantActivatedMail` | Mail, queued | Super admin reactivates the tenant | Owner |
| `NewChatMessageMail` | Mail, queued | Tenant user sends a chat message | Two hardcoded OmniPOS support addresses |

The notification bell (any tenant user) lists notifications with an unread count, mark one or all as read, and polls every 30 seconds.

### 4.20 Users, roles, and permissions

- **Team / Users page** (`users.view`). Adding users, CSV import, and activating or deactivating users are **owner only** (and need `users.edit-role` at the route). Editing and removing use `users.edit-role` and `users.remove`. The owner's own row can only be edited by the owner; nobody can remove themselves.
- New users are created with name, email, password, role, and branch (defaults to the newest active branch). They count against the plan's user limit.
- The owner can set a POS PIN for any user; each user can set their own. PINs are 4-6 digits, hashed, and unique within the tenant.
- **Roles page** (`roles.*`): create custom roles, pick permissions grouped by area, edit, delete. System roles cannot be deleted and roles assigned to users cannot be deleted.

Permission groups (41 permissions):

| Group | Permissions |
| --- | --- |
| branches | view, create, edit, delete |
| users | view, invite, edit-role, remove |
| roles | view, create, edit, delete |
| products | view, create, edit, delete (also covers categories and add-ons) |
| inventory | view, manage (also covers purchase orders and stock transfers) |
| pos | access, discount, void (void also covers refunds) |
| orders | view, manage (also covers customers) |
| reports | view (also AI Insights) |
| shifts | view, manage |
| settings | manage (also Receipt Design) |
| suppliers | view, create, edit, delete |
| tables | view, create, edit, delete |
| promotions | view, create, edit, delete |
| kitchen | access, manage |

`users.invite`, `shifts.manage`, and `kitchen.manage` are not checked by any route; `users.invite` only toggles a dashboard quick action. `pos.discount` is checked in the POS screen only.

### 4.21 Settings

Route `/{tenant}/settings`, permission `settings.manage`. Stored in the tenant's `data` JSON.

- **Store information:** store name, address, phone.
- **Tax:** rate (0-100%), label, inclusive or exclusive.
- **Receipt basics:** header and footer text.
- **Currency:** PHP, USD, EUR, GBP, JPY, KRW, CNY, SGD, AUD, CAD, INR, THB, MYR, IDR, VND (formatting only, no conversion).
- **Appearance and language:** default theme (light, dark, system) and default language (English, Japanese, Filipino).
- **Plan & Billing:** current plan and limits, request-a-change link.
- **Business type change (owner only):** pick a new type and type `CHANGE` to confirm. This deletes **all products and categories** (and, through cascades, inventory, branch pricing, variations, add-on and supplier links, plus PO and transfer items), sets the new type, and seeds the new default categories. It is logged as `business_type.changed`.

**Receipt Design** (`/{tenant}/receipt-customization`, Enterprise only): logo upload (up to 1 MB), header, footer, thank-you message, toggles to show address, phone, customer, table, order type, and tax breakdown, and paper width 58 mm or 80 mm, with a live preview.

### 4.22 Activity and audit logs

- Tenant `activity_logs` records: `order.completed`, `order.voided`, `order.refunded`, `user.created`, `user.updated`, `user.activated` / `user.deactivated`, `user.removed`, `users.imported`, `role.created`, `role.updated`, `role.deleted`, `business_type.changed`. There is no tenant-facing activity log page; super admins see it per tenant.
- Stock changes are audited separately in `inventory_adjustments`.
- `user_logins` records every web login with IP and user agent.
- Super admin actions are recorded in `admin_activity_logs` (created, updated, deleted tenants, users, admins, plans, promo codes, release notes; subscription plan changes, cancellations, reactivations, trial extensions; password resets; settings updates).

### 4.23 OmniChat support

A chat widget available to every tenant user. Conversation starters: Report an Issue (opens a short form and gets an automatic acknowledgement), Billing & Subscription, Feature Request, General Inquiry. Messages up to 2,000 characters with an optional image attachment (up to 2 MB). Each message emails the support team. Users see admin replies, unread counts, and are asked to acknowledge when a conversation is marked resolved. Statuses: Open, Closed, Resolved.

### 4.24 CSV import and export

- **Import:** users only. CSV with required columns `name`, `email`, `role` and optional `branch`. The file is validated first (missing columns, invalid or duplicate emails, unknown role or branch) with row-level errors. On import, each user gets a random 12-character password and the owner receives the list of credentials to hand out.
- **Export:** reports (CSV or PDF, seven report types). Receipts and KOTs export as PDF. There is no CSV export for orders, products, or customers.

### 4.25 Deactivation handling

- **Tenant deactivated by a super admin:** every tenant URL shows a full-screen "Account Deactivated" page (no sidebar) with the business name and a support email link. Data is kept. The owner receives an email on deactivation and on reactivation.
- **Individual user deactivated by the owner:** that user gets a 403 for the tenant and cannot log in at the POS by PIN.

### 4.26 Navigation shell and command palette

- Flat sidebar grouped as: pinned (Dashboard, POS, Kitchen), Sell (Orders, Customers, Promotions, Tables), Catalog (Products, Categories, Add-ons), Stock (Inventory, Purchase Orders, Stock Transfers, Suppliers), Insights (Reports, AI Insights), People (Team, Roles, Shifts, Schedules), Business (Settings, Branches, Receipt Design).
- Items the user lacks permission for are hidden. Plan-locked items show a crown and open an upgrade prompt. Items disabled by a branch toggle show dimmed with a hint.
- **Command palette** (Ctrl/Cmd+K) jumps to the same pages using the same rules, plus quick actions. It does not search records.
- Header: breadcrumbs, notification bell, language switcher, theme toggle, user menu. Toasts for success and error messages. Shared UI pieces: PageHeader, StatCard, EmptyState, ConfirmDialog, StatusBadge, InfoTip and IconAction (tooltips and accessible icon buttons).

### 4.27 Account settings (all users)

Profile (name, email, delete account), Security (change password, enable TOTP two-factor authentication with recovery codes), Appearance.

---

## 5. Super admin panel

Separate login at `/admin/login` using its own `admins` table and `admin` guard (throttled to 5 attempts per minute). All pages require the admin guard.

| Area | What it offers |
| --- | --- |
| Dashboard | Platform stats (total and active tenants, users, estimated MRR, trial tenants, new signups, churn rate, average revenue), revenue trend, plan distribution, recent admin activity, recent user logins, login trend, user activity stats, marketing page visit stats and trend, top referrers |
| Tenants | List with search and filters (active, business type, plan), create (owner, plan, business type), view, edit, delete, activate or deactivate (emails the owner) |
| Tenant activity | Per tenant: logins today, active users in 24 h, orders today, open shifts, and a merged timeline of logins, activity logs, shift opens and closes, orders, and product creations, filterable by user, event type, and date (25 per page) |
| Users | Platform user accounts: list, create, view, edit, delete, reset password, toggle email verified |
| Admins | Manage admin accounts |
| Plans | Create, edit, delete, activate or deactivate plans (name, slug, price, feature list, limits) |
| Promo codes | Create, edit, delete codes; create page has quick presets for Pro and Enterprise |
| Subscriptions | List, view, change plan, cancel, reactivate, extend trial |
| Reports | Revenue (estimated from plan list prices of active subscriptions), Tenants, Users, Subscriptions |
| Release notes | Create, edit, delete, publish notes with typed items (New Feature, Bug Fix, Improvement); templates (Major Release, Feature Drop, Hotfix, Custom) and a live preview. Published notes appear in the tenant Welcome Back modal |
| Activity log | Paginated admin activity history |
| Settings | App name, registration enabled, default plan, trial days, support email, currency symbol |
| Chat inbox | All tenant conversations with filters, live polling, reply, mark read, close, resolve, unread count |

---

## 6. Marketing site

All public pages record a page visit (URL, IP, user agent, referrer) used for admin analytics and the landing page counters. SEO work includes titles, descriptions, Open Graph and Twitter tags, JSON-LD (SoftwareApplication and Organization), `robots.txt`, and `sitemap.xml`. Domain: `omnipos.shop`.

| Page | Contents |
| --- | --- |
| Welcome (`/`) | Hero, live stats bar (total visits, visitors today and this month, active businesses; cached 5 minutes), feature overview and showcases, testimonials, full feature list and advanced capabilities, how it works, business types grid, pricing with monthly/annual toggle, FAQ, call to action |
| Features (`/features`) | Nine modules: Point of Sale & Checkout, Inventory Control, Supply Chain, Reports & Analytics, Multi-Branch Management, Shifts & Cash Reconciliation, Kitchen Display & Table Management, Promotions & Discounts, Customer Management. Trust section: role-based access, 2FA, audit trail, isolated tenant data, receipt control, languages |
| How It Works (`/how-it-works`) | Six steps (create account, first branch and PIN, add products and team, open a shift and sell, watch from anywhere, grow), "a day with OmniPOS", business-type defaults, FAQ |
| Pricing (`/pricing`) | Plans from the database in pesos, monthly/annual toggle, plan limit comparison, why owners switch, pricing FAQ. Sign-up links carry the chosen plan |
| About (`/about`) | Problem with traditional POS, one app for everything, cost savings, why a web app |
| Privacy Policy (`/privacy`) | 12 sections, including data isolation and multi-tenancy, cookies, retention, rights |
| Terms of Use (`/terms`) | 15 sections, including plans and billing, data ownership, 2FA, governing law (Philippines) |

**Annual pricing:** the toggle shows a 20% discount (for example Pro 1,499 becomes 1,199.20 per month, billed 14,390.40 per year). This is display only: the chosen billing period is passed to the register URL but is not stored, and no payment is taken.

Testimonial quotes on the Welcome page are written into the page source; whether they come from real customers is not known from the code.

---

## 7. Data model summary

Central (platform) tables:

| Table | Stores | Key relations |
| --- | --- | --- |
| `users` | Global login accounts, password, Google ID, 2FA secrets, email OTP code, last seen release note | Many tenants through `tenant_users` |
| `admins` | Super admin accounts | Separate guard |
| `tenants` | Business (UUID id, name, slug, business type, settings JSON in `data`, owner, active flag) | Owner is a user |
| `tenant_users` | Membership: user, tenant, role, branch, POS PIN hash, active, last login | Joins users, tenants, roles, branches |
| `plans` | Name, slug, price, feature list, max branches/users/products, active | |
| `tenant_subscriptions` | Tenant, plan, status, promo code, trial end, end | One per tenant |
| `promo_codes` | Platform promo codes | Used by subscriptions |
| `permissions`, `roles`, `role_permission` | Global permissions, per-tenant roles, pivot | Roles belong to tenants |
| `system_settings` | Key/value admin settings | |
| `release_notes` | Title, version, summary, items JSON, published | |
| `chat_conversations`, `chat_messages` | Support chat with attachments and read state | Tenant and user |
| `user_logins`, `page_visits`, `admin_activity_logs` | Login history, marketing analytics, admin audit | |
| `notifications` | Laravel database notifications (bell) | |
| `sessions`, `cache`, `jobs`, `failed_jobs`, `job_batches`, `password_reset_tokens` | Framework tables | |

Tenant business tables (all carry `tenant_id` directly or through a parent):

| Table | Stores | Key relations |
| --- | --- | --- |
| `branches` | Branch details and feature settings JSON | Tenant |
| `categories`, `products` | Catalog (product price, cost, SKU, image, food flag) | Product belongs to category |
| `variation_groups`, `variation_options` | Product variations and price modifiers | Product |
| `addons`, `product_addon` | Add-ons and product links | |
| `branch_product` | Per-branch availability and custom price | Branch and product |
| `inventory`, `inventory_adjustments` | Stock per product per branch, thresholds, reorder settings; full movement history | Product, branch |
| `customers` | Customer records | Orders |
| `orders` | Header: numbers, totals, discount, promotion, tax, type, status, branch, shift, table, kitchen status, void and refund fields, held time, receipt token, ID-discount customer details | Branch, customer, shift, table, promotion, creator |
| `order_items`, `order_item_variations`, `order_item_addons` | Line items with price snapshots and notes | Order |
| `payments` | One or more per order; negative rows for refunds | Order, refund |
| `refunds`, `refund_items` | Refund header and items | Order, order items |
| `shifts`, `shift_schedules` | Cash shifts and weekly schedules | User, branch |
| `tables` | Dine-in tables | Branch |
| `promotions` | Tenant promotions and presets | Orders |
| `suppliers`, `product_supplier` | Suppliers and product links (cost, supplier SKU, preferred) | |
| `purchase_orders`, `purchase_order_items` | POs and lines | Supplier, branch |
| `stock_transfers`, `stock_transfer_items` | Transfers between branches | Source and destination branch |
| `activity_logs` | Tenant audit events | User |

Note: 20 of the tenant migrations live in `database/migrations/tenant/`, a subfolder that Laravel's standard migrate command does not scan (see section 10).

---

## 8. Default roles and permissions

Created for every new tenant. Owners can edit permissions on the Admin, Cashier, and Staff roles and create more roles.

| Role | Permissions | In practice |
| --- | --- | --- |
| Owner | All 41, always | Everything. Also the only person who can add, import, activate or deactivate users, set other users' PINs, and change the business type |
| Admin | All except `settings.manage` | Runs operations: branches, team (except owner-only actions), roles, catalog, inventory, POS, voids and refunds, reports, AI Insights, suppliers, tables, promotions, kitchen. Cannot open Settings or Receipt Design. Receives low stock alerts for their branch |
| Cashier | `pos.access`, `pos.discount`, `orders.view`, `products.view`, `inventory.view` | Sells at the POS with manual discounts, views orders and customers of their branch, views products and stock. Cannot void or refund |
| Staff | `branches.view`, `users.view`, `products.view`, `inventory.view`, `orders.view` | Read-only access to branches, team, products, stock, orders, customers. No POS |

Kitchen staff are not a default role; an owner would create one with `kitchen.access`.

---

## 9. Business types and enums

### Business types (17)

Each type seeds its own default categories at signup or after a business type change.

| Type | Default categories |
| --- | --- |
| Grocery / Convenience Store | Beverages, Snacks, Canned Goods, Dairy & Eggs, Bread & Bakery, Frozen Foods, Condiments & Sauces, Rice & Grains, Personal Care, Household |
| Cafe / Coffee Shop | Hot Coffee, Iced Coffee, Tea, Frappes, Non-Coffee Drinks, Pastries, Sandwiches, Cakes & Desserts, Add-ons |
| Restaurant | Appetizers, Soups, Salads, Main Courses, Pasta & Noodles, Seafood, Desserts, Beverages, Kids Menu, Sides |
| Bar & Pub | Beer, Cocktails, Wine, Spirits, Non-Alcoholic, Bar Chow, Pulutan |
| Retail Store | Electronics, Home & Living, Toys & Games, Stationery, Sports & Outdoors, Health & Beauty, Bags & Luggage, Accessories |
| Clothing & Accessories | Men's Wear, Women's Wear, Kids' Wear, Footwear, Activewear, Underwear & Socks, Accessories, Bags |
| Bakery | Bread, Cakes, Cupcakes, Pastries, Cookies & Bars, Pies & Tarts, Doughnuts, Drinks |
| Pharmacy / Drugstore | Prescription Medicines, Over-the-Counter, Vitamins & Supplements, Personal Care, Baby Care, First Aid, Medical Supplies, Beauty & Skincare, Health Foods |
| Hardware Store | Hand Tools, Power Tools, Electrical, Plumbing, Paint & Finishes, Fasteners, Building Materials, Safety Equipment, Garden & Outdoor |
| Food Manufacturing | Raw Materials, Finished Products, Packaging, Frozen Goods, Beverages, Condiments & Sauces, Snacks, Supplies |
| Beauty Salon & Spa | Hair Services, Nail Services, Skin & Facial, Massage & Spa, Waxing & Threading, Makeup, Retail Products, Packages |
| Barbershop | Haircuts, Shave & Beard, Hair Color & Treatment, Packages, Hair Products, Grooming Tools |
| Pet Shop | Dog Food, Cat Food, Treats & Snacks, Accessories, Toys, Grooming, Health & Medicine, Aquarium & Small Pets |
| Electronics & Gadgets | Phones & Tablets, Computers & Laptops, Accessories, Audio, Gaming, Parts & Components, Repair Services, Power & Batteries |
| Laundry Shop | Wash & Fold, Dry Cleaning, Ironing & Pressing, Bulky Items, Add-ons, Supplies |
| Milk Tea & Dessert Shop | Milk Tea, Fruit Tea, Cheese Cream Series, Frappes & Smoothies, Coffee, Desserts, Snacks, Add-ons |
| Other | General, Miscellaneous |

### Enums worth knowing

| Enum | Values |
| --- | --- |
| OrderStatus | pending, completed, voided, refunded |
| OrderType | dine_in, take_out |
| KitchenStatus | new, preparing, ready, served |
| PaymentMethod | cash, card, e_wallet, bank_transfer, other, online |
| PaymentStatus | pending, completed, failed |
| DiscountType | percentage, fixed |
| PromotionType | percentage, fixed, buy_x_get_y, student, pwd, senior_citizen |
| AdjustmentType | purchase, sale, return, damage, correction, initial, transfer_out, transfer_in, refund |
| PurchaseOrderStatus | draft, sent, partial, received, cancelled |
| TransferStatus | pending, in_transit, completed, cancelled |
| TableStatus | available, occupied, reserved, maintenance |
| ShiftStatus | open, closed |
| SubscriptionStatus | active, trial, cancelled, expired, past_due, pending |
| ChatStatus | open, closed, resolved |
| ReleaseNoteItemType | feature, fix, improvement |

Document number formats: `ORD-`, `HLD-`, `REF-`, `PO-`, `TRF-` followed by `YYYYMMDD-NNNN`, numbered per tenant per day.

---

## 10. Security, privacy, and reliability

### What is in place

- **Authentication:** Fortify login (5 attempts per minute per email and IP), password reset, email verification by 6-digit code (15-minute expiry, verify endpoint throttled 6 per minute), optional TOTP 2FA with confirmation and recovery codes (2FA challenge throttled 5 per minute), Google sign-in (throttled 10 per minute). Login alert email on every login.
- **Password policy in production:** at least 12 characters, mixed case, letters, numbers, symbols, and not found in known breaches.
- **Tenant isolation:** path-based tenant resolution plus membership check on every tenant request; services filter by tenant; checkout validation scopes product, customer, table, and promotion IDs to the tenant.
- **Branch isolation:** orders, POS, kitchen, and feature checks use the user's assigned branch.
- **Authorization:** route-level permission middleware, plan middleware, branch feature middleware; owner-only checks for sensitive staff actions; protected system roles.
- **POS PIN:** hashed, unique per tenant, verify and set endpoints throttled (5 per minute), PIN proof kept in the session for 8 hours and re-checked at checkout.
- **Rate limits:** checkout 60 per minute, user and role management 30 per minute, admin login 5 per minute, promo validation 10 per minute.
- **Validation:** Form Requests on all major writes (checkout, refunds, products, promotions, inventory, settings, receipts, users import).
- **Data integrity:** DB transactions and row locks for checkout, numbering, inventory changes, refunds; stock cannot go negative; production blocks destructive database commands.
- **Audit:** tenant activity logs, inventory adjustment history, login history, admin activity log, per-tenant activity timeline for admins.
- **Receipt links** use 64-character random tokens.

### Known limitations and gaps observed in the code

These were seen while reading the code. They have not been confirmed by running the app.

1. **Preset ID discounts may be computed differently on server and client.** The POS screen applies Student, PWD, and Senior Citizen presets as 20% of the order, but `PosService::checkout` (and `applyPromoCode`) treat every promotion type other than `percentage` as a fixed amount. For a preset worth 20, the server would deduct 20 currency units, not 20%. Buy X Get Y has no X or Y fields and is also treated as a fixed amount.
2. **Price inputs trusted from the browser.** Variation price modifiers and add-on prices are taken from the checkout request instead of being looked up from the database.
3. **`pos.discount` is enforced only in the UI.** The checkout endpoint does not check it. `users.invite`, `shifts.manage`, and `kitchen.manage` are not enforced by any route.
4. **Scheduled jobs may not run in production.** The Railway start command runs the web server and a queue worker, but not the Laravel scheduler. Unless a separate cron service exists outside the repo, auto-closing stale shifts and end-of-day summary emails will not run.
5. **Tenant migrations folder.** 20 migrations in `database/migrations/tenant/` (suppliers, variations, add-ons, transfers, purchase orders, tables, promotions, refunds, notifications, and more) are not picked up by the standard migrate step, and no code loads that folder. memory.md flags this as a critical issue and it is still not fixed in code. Whether production was patched by hand is unclear.
6. **Possible double event handling.** Listeners are registered explicitly in `AppServiceProvider`, and Laravel 12 also auto-discovers listeners in `app/Listeners`. That can make low-stock and order listeners run twice. This needs checking with `event:list`.
7. **Plan features not enforced.** Only limits, AI Insights, and Receipt Design are plan-gated. "API access" is advertised for Enterprise but there is no API.
8. **No billing.** Paid plans are unlocked with promo codes; the annual discount is display only; subscription revenue in admin reports is an estimate from list prices (promo discounts ignored).
9. **Admin settings are not used.** Registration enabled, trial days, default plan, support email, and currency symbol are saved but nothing reads them. New signups never start on a trial.
10. **Admin panel hardening.** No 2FA for admin accounts; the seeder creates `admin@omnipos.com` with password `password`, which must be changed in any real environment. Chat notification recipients are hardcoded personal and company addresses.
11. **PIN uniqueness message.** Setting a PIN that another user already has returns "already in use by another user", which tells the person that the PIN is valid for someone else. PIN verification checks the hash of every PIN in the tenant, which gets slower as staff grow.
12. **Validation scoping gaps.** Product `addon_ids` and `branch_ids`, and user `branch_id` and `role_id`, use plain `exists` rules without a tenant filter. Users and roles are re-checked against the tenant afterwards; whether add-on and branch IDs are re-checked in the service was not verified.
13. **Refund edge cases.** Partial refunds use the item unit price and ignore order-level discounts and tax, and the requested quantity is not checked against the quantity sold (only the total amount is capped).
14. **Senior Citizen rules.** The age 60+ check is in the browser only, and there is no VAT-exemption handling for Senior or PWD sales.
15. **Business type change is destructive.** It deletes every product and category (and their inventory and links) after a typed confirmation, owner only. There is no undo.
16. **Marketing claims vs code.** The Features page says "50+ permissions" (there are 41) and that product and settings changes are in the audit trail (they are not; only orders, users, roles, and business type changes are logged).
17. **Unused toggles and payment methods.** `customer_loyalty` and `delivery` branch toggles do nothing yet. Online payment shows "Soon". Bank Transfer and Other exist in the data model but not in the POS.
18. **Partial translation.** Only about a dozen frontend files use translations; emails and server messages are English only. Reports format currency as PHP regardless of the tenant currency (per modern-ui-omnipos.md).
19. **Testing.** Only the starter-kit tests exist (authentication, email verification, password reset, 2FA, profile, security, dashboard). There are no tests for POS, inventory, refunds, or tenancy. Tests run on SQLite while some queries are PostgreSQL-only.
20. **CI location.** The GitHub workflows are in `myapp/.github/workflows`, but the Git repository root is the folder above `myapp/`. GitHub only runs workflows from `.github/` at the repository root, so these likely never run.
21. **Secrets in the working folder.** `prod/env-prod.md` at the repository root contains production storage credentials. Confirm it is not committed (and rotate the keys if it ever was).
22. **No offline mode or real-time push.** The POS needs a connection; live screens poll.
23. **`stancl/tenancy` installed but unused,** which can confuse new developers (it also registers its own routes).

---

## 11. Project structure

```
OmniPOS---Multi-Tenant-Web-POS-Application/
  README.md, project.md, features.md, memory.md, modern-ui-omnipos.md   Product docs, change log, UI plan
  prod/env-prod.md                    Production environment notes (contains secrets, see section 10)
  myapp/                              The Laravel application
    app/
      Actions/Fortify/                Registration (CreateNewUser) and password reset
      Console/Commands/               shifts:close-stale, reports:daily-summary, products:migrate-images
      Enums/                          16 enums (statuses, types, business types with default categories)
      Events/, Listeners/             Domain events and their handlers (emails, notifications, auto-reorder, logging)
      Http/Controllers/Tenant/        30 tenant controllers (POS, orders, inventory, reports, settings, ...)
      Http/Controllers/SuperAdmin/    Admin panel controllers and admin login
      Http/Controllers/Auth/          Google OAuth, email OTP verification
      Http/Middleware/                IdentifyTenant, CheckPermission, CheckPlanAccess, CheckBranchFeature, admin guard, Inertia shared props, page visit tracking
      Http/Requests/                  Form Request validation (Tenant, SuperAdmin, Settings)
      Jobs/                           SendReceiptEmail
      Mail/, Notifications/           Mailables and notifications (see 4.19)
      Models/                         Central models (User, Tenant, Plan, Role, ...)
      Models/Tenant/                  26 tenant business models (Order, Product, Inventory, Shift, ...)
      Services/Tenant/                Tenant business logic
      Services/Central/               Platform and admin logic
      Services/PlanLimitService.php   Plan limit enforcement
    bootstrap/app.php                 Route files, middleware aliases
    config/                           Standard Laravel config plus product_disk and Google OAuth
    database/migrations/              Central and most tenant tables
    database/migrations/tenant/       20 more tenant migrations (not auto-loaded, see section 10)
    database/seeders/                 Plans, permissions, super admin, sample LEGO store catalog
    resources/js/
      pages/                          Inertia pages: marketing (Welcome, Features, ...), auth/, settings/, tenant/ (about 60 pages), admin/ (about 30 pages), public/Receipt
      layouts/                        Tenant, POS, Admin, Auth, Public, App, Settings layouts
      components/                     App components (sidebar, command palette, POS dialogs, chat, notification bell, receipts) and ui/ (shadcn-vue kit)
      composables/                    useTenantNav, usePermissions, usePlanLimits, usePosAuth, usePosShift, usePrinter, useBarcodeScanner, useKitchenDisplay, useOmniChat, useCurrency, useAppearance, useToast, ...
      lang/                           en.json, fil.json, ja.json
      routes/, actions/               Wayfinder-generated typed route helpers
      types/                          TypeScript models
    resources/views/                  app.blade.php shell, email templates, thermal receipt, KOT, report PDF, mail theme
    routes/                           web.php (marketing, auth extras, public receipt), tenant.php, admin.php, settings.php, console.php
    tests/                            Starter-kit feature tests
    railway.toml                      Railway build and deploy settings
    .github/workflows/                lint.yml, tests.yml
```

---

## 12. Current status and roadmap

### Recently shipped (per docs and git history)

- **Core POS gaps closed** (from `myapp/featuretobeimplement.md`, all present in code): per-item notes, customer purchase history, full and partial refunds, hold and recall orders, split payments, in-app low stock alerts, digital receipts (email and public link), event foundation, auto-close stale shifts, end-of-day summary email, low stock emails, queued receipt emails, auto stock reorder.
- **April 2026** (`features.md`, `memory.md`): mandatory branch assignment for users, inventory rows created on branch creation, per-tenant user activity page for admins, branch availability on products, compact product form, food / non-food products with initial stock, admin email on new chat messages, tenant deactivation page and owner emails, release notes admin with templates, Welcome Back modal, weekly shift schedules, landing page SEO, email template fixes.
- **Later additions found in code:** preset ID discounts with customer verification, hours rendered on shifts, business type change, Plan & Billing card, receipt design.
- **UI modernization** (`modern-ui-omnipos.md`, git commits phases 0 to 4): inventory stat fixes, upgrade modal feature lock, InfoTip and IconAction, toasts, flat sidebar with locked and disabled states, command palette, aria labels, clickable rows, tooltips across orders, POS, inventory, shifts, branches, reports, roles, promotions, shared PageHeader, StatCard, EmptyState, ConfirmDialog, StatusBadge, plan usage display.

### Planned (as stated in docs)

- **UI plan, remaining phases** (`modern-ui-omnipos.md`): Phase 5 POS checkout redesign (fewer modals, undo after Clear, 44 px touch targets, more cashier shortcuts), Phase 6 i18n rollout page by page with a CI key check, Phase 7 consistency and polish (one card style, status colour vocabulary, dark mode and accessibility pass). Open questions include in-app billing, whether Delivery and Customer Loyalty are coming, and the Customers and Schedules permission mapping.
- **Tier 3, customer engagement** (`featuretobeimplement.md`): loyalty points (the `customer_loyalty` toggle already exists), customer insights, birthday rewards, customer groups with group pricing.
- **Tier 4, advanced:** WebSocket real-time dashboard and kitchen (replacing polling), offline mode / PWA, credit and tab system, employee time clock using PINs, expense tracking and profitability, multi-currency.
- **Product UI:** online payment ("Soon" in POS, "coming soon" in Plan & Billing).
- **Open issue:** tenant migrations subfolder (memory.md Issue #1, fix not chosen).

---

## 13. Glossary

| Term | Meaning |
| --- | --- |
| Tenant | One business account in OmniPOS, with its own URL slug, data, roles, and subscription |
| Owner | The user who created the tenant; has every permission and some owner-only powers |
| Branch | A physical store or location under a tenant; staff, stock, prices, tables, and feature toggles are per branch |
| Branch feature toggle | A per-branch switch (POS, inventory, kitchen, discounts, dine-in, etc.) that turns modules on or off |
| Plan | Free, Pro, or Enterprise; sets limits on branches, users, products and unlocks some features |
| Promo code (platform) | Admin-issued code that unlocks or discounts a paid plan at signup |
| Promotion (tenant) | A discount code or preset the business applies at the POS |
| Preset discount / ID discount | Built-in Student, PWD, and Senior Citizen discounts that record the customer's ID details |
| PWD | Person with Disability (Philippine discount category) |
| POS | Point of sale: the checkout screen |
| PIN | 4-6 digit code a cashier uses to sign in at a shared POS |
| Shift | A cashier's working session at the POS, from opening cash to closing count |
| Expected cash / difference | Starting cash plus cash sales; counted cash minus expected cash |
| Held order | A saved cart that has not been paid yet (park and recall) |
| Void | Cancel a completed sale entirely and return stock |
| Refund | Return money for all or part of a sale; the sale stays on record |
| Split payment | Paying one order with more than one payment method |
| KDS | Kitchen Display System: the kitchen screen showing incoming orders |
| KOT | Kitchen Order Ticket: the printed slip the kitchen works from |
| PO | Purchase Order sent to a supplier to restock a branch |
| Stock transfer | Moving stock from one branch to another |
| Reorder point / quantity | Stock level that triggers an automatic draft PO, and how many units it orders |
| Low stock threshold | Stock level that triggers a low stock alert |
| Adjustment | Any recorded change to stock, with type, before, after, and reason |
| Food product | A made-to-order item that is not stock tracked |
| Variation / add-on | A product option that changes price (size) / an extra item added to a product |
| EOD summary | End-of-day email to the owner with the day's sales |
| Release notes | Product update notes written by admins and shown to tenants after login |
| OmniChat | The in-app support chat between tenant users and OmniPOS admins |
| Inertia | The library that lets Laravel controllers render Vue pages directly |
| Super admin | OmniPOS staff account that manages the whole platform |
