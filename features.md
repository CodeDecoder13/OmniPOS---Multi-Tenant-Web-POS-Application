# Upcoming Release — April 4, 2026

## New Features

### Mandatory Branch Assignment for Users
Every user must now be assigned to a specific branch. The "All Branches" option has been removed from user management, and the system auto-assigns users to the newest branch throughout the entire flow.

**Highlights:**
- **Add/Edit User dialogs** — Removed "All Branches" dropdown option. Branch defaults to the newest active branch.
- **Setup Wizard** — When the owner creates the first branch during onboarding, they are automatically assigned to it (previously left as `null`).
- **Backend fallback** — `UserController` store/update methods default to the newest active branch if `branch_id` is null, as a safety net.
- **Existing data migration** — One-time migration assigns all existing users with `null` branch_id to their tenant's newest active branch.
- **Table display** — Users page shows the newest branch name as fallback for any legacy users without a branch.

---

### Auto-Initialize Inventory on Branch Creation
When a new branch is created (via setup wizard or Branches page), inventory records are automatically initialized for all existing products with quantity 0. This ensures products appear in the Inventory page immediately and can be stocked up before selling via POS.

**Highlights:**
- **`BranchService::create()`** — After creating a branch, bulk-inserts inventory records (quantity = 0) for every product in the tenant.
- **Works everywhere** — Applies to both the setup wizard (`SetupController`) and the Branches management page (`BranchController`).
- **Existing data fix** — Ran a one-time script to create missing inventory records for all current product/branch combinations.

---

### Tenant User Activity Tracking in Admin Panel
Admins can now view a per-tenant "User Activity" page at `/admin/tenants/{id}/activity`, accessible from the tenant detail view. It aggregates data from existing tables — no new migrations needed.

**Highlights:**
- **Summary stat cards** — Logins Today, Active Users 24h, Orders Today, Open Shifts.
- **Unified activity timeline** — Merges events from 6 sources: user logins, activity logs, shift opens, shift closes, orders, and product creations into a single sorted feed.
- **Filters** — Filter by user, event type, and date range. Default shows last 30 days.
- **Colored event badges** — Login (blue), Activity (purple), Shift Open (green), Shift Close (orange), Order (teal), Product Created (indigo).
- **Expandable properties** — Activity log entries show JSON properties in an expandable row.
- **Pagination** — 25 events per page with standard pagination controls.
- **`TenantActivityService`** — New service in `app/Services/Central/` with `getSummaryStats()`, `getTimeline()`, and `getTenantUsers()` methods.
- **Tenant Show page** — Added "User Activity" button next to Edit in the actions area.

---

## Improvements

### Branch Availability on Products
Products can now be restricted to specific branches. The Create and Edit product pages include a "Branch Availability" section where users can choose which branches a product is available at.

**Highlights:**
- **"All branches" default** — Products are globally available by default. Unchecking reveals individual branch checkboxes.
- **POS integration** — Restricted products only appear in their assigned branch's POS. Uses the existing `branch_product` pivot table.
- **Smart sync** — Selecting all branches (or re-checking "All branches") removes pivot entries, keeping the product globally available with no unnecessary data.
- **Validation** — `branch_ids` validated as optional array of existing branch IDs.
- **`ProductService::syncBranchAvailability()`** — New method handles upsert logic for the `branch_product` pivot.

### Compact Product Form Layout
The Create and Edit product pages now use a persistent two-column layout instead of stacking sections full-width below the image. Secondary sections (Barcode, Branch Availability) fill the space below the product image in the left column, while Variations, Add-ons, and action buttons stay in the right column alongside Product Details. Reduces scrolling significantly.

### Admin Email Notification on New Chat Message
When a tenant user sends a chat message, an email notification is automatically dispatched to the admin team containing the user's name, email, tenant name, and message body. Uses OmniPOS teal branding consistent with existing email templates.

**Highlights:**
- **Queued Mailable** — `NewChatMessageMail` implements `ShouldQueue` for async sending, avoiding request delays.
- **Recipients** — Sends to `rhuzzel.paramio@omnipos.shop` and `boyparamio@gmail.com`.
- **Custom Blade template** — Teal gradient header with OmniPOS logo, card layout showing sender details (name, email, tenant, subject), and the message body.
- **Subject line** — `"New Chat Message from {userName} ({tenantName})"` for easy inbox scanning.
- **Triggered in `ChatService::sendUserMessage()`** — Dispatched after the conversation is updated, before any auto-reply logic.
