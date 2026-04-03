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
