# OmniPOS Modern UI/UX Plan (tenant app)

Status: plan only, nothing implemented. Date: 2026-10-24.
Scope: everything a shop user sees after login (`myapp/resources/js/pages/tenant/**`, the sidebar/header shell, POS, kitchen, settings). The marketing site and the super-admin panel are out of scope.

Goal: make the app easy to navigate for owners, managers, and cashiers, explain confusing things with hover/focus popups, and give every screen one consistent modern look.

## How this plan was made

- Opus agent read the navigation shell, every page under `pages/tenant/**`, the report/kitchen components, and `lang/en.json`.
- I re-checked the four biggest claims against the code (marked **verified** below). Everything else is the agent's reading and its line numbers are approximate; re-confirm when you touch a file.
- Lenses applied: Impeccable (Operate mode, product UI), Emil Kowalski's design-engineering rules (tooltip and motion details), frontend-design (restraint, one signature), ponytail (smallest change that works, reuse what is installed), ui-ux-pro-max (accessibility and interaction checklist).
- Not applied, on purpose: taste-skill is for landing pages and portfolios and its own scope note excludes dashboards and product UI. Its anti-slop rules (no AI-purple, no fake data, one radius system, real contrast) are still used as a checklist.
- Not run: the ui-ux-pro-max design-system search needs Python, which is not installed here. Impeccable has no `PRODUCT.md` for this project; run `$impeccable init` before starting the redesign phases so decisions are recorded.

## Design direction (Operate mode)

The user is in the middle of a task. The interface should disappear into the task.

- **Keep the identity.** Teal accent stays. It marks primary actions, the current selection, and state only, never decoration.
- **One type family**, fixed rem scale, no fluid headings. Numbers in tables and totals use tabular figures.
- **One surface system.** Today three card styles coexist (`glass-card`, gradient icon tiles, plain `bg-white dark:bg-gray-900`). Pick the shadcn `Card` with `bg-card` and tokens, delete the rest.
- **One radius scale** (inputs 8px, cards 12px, pills full) and one icon family (lucide, already installed). One `strokeWidth`.
- **Every control has the full state set:** default, hover, focus-visible, active, disabled, loading, error. Buttons get `active:scale-[0.97]` (160ms, ease-out).
- **Skeletons over spinners**, empty states that teach ("No suppliers yet. Add one to start purchase orders."), no "nothing here".
- **Modals are the last resort.** Prefer inline, popover, or undo toast. Delete confirmations stay modal, and use one shared `ConfirmDialog`.
- **Dark mode is a requirement** for every new component. Fix charts that read `isDark` once and never update.

### Motion rules (from the Emil skill, tuned for a POS)

| Case | Rule |
| --- | --- |
| Anything triggered by keyboard or a barcode scan | No animation. Cashiers repeat it hundreds of times a day. |
| Tooltips | 400ms open delay, then instant for neighbours (no delay, no animation). 125ms fade + `scale(0.97 -> 1)`, `ease-out`, `transform-origin` at the trigger. |
| Dropdowns, popovers | 150-200ms, ease-out. Never `ease-in`. Never animate from `scale(0)`. |
| Cart rows, toasts | CSS transitions (interruptible), not keyframes. |
| Reduced motion | Keep opacity fades, remove movement. |
| Touch devices | Gate hover-only effects behind `@media (hover: hover) and (pointer: fine)`. Info popups become tap-to-open. |
| Page load | No choreography. Product UI loads straight into the task. |

## Verified bugs (fix first)

1. **Locked sidebar items never show the upgrade crown.** `TenantSidebar.vue:139` removes plan-mismatched items inside `filterByPermission`, so `requiresUpgrade()` (line 144) can never be true. Basic-plan owners never learn AI Insights or Receipt Design exist. **Verified.**
2. **Inventory "Low Stock" and "In Stock" cards count only the current page.** `inventory/Index.vue:50-56` filters `props.inventory.data`. **Verified.** Needs counts from the server.
3. **Receipt Design upgrade popup says "branch limit".** `receipt-customization` passes `resource="branches"` to `UpgradePlanModal`. **Verified.** Needs a feature-lock variant.
4. **POS uses `alert()` for errors** (5 places). **Verified.** Replace with toasts.
5. **Almost no translation.** `$t` / `useI18n` count is 0 in `pos/Index.vue`, `Dashboard.vue`, and `TenantSidebar.vue`, although `en.json` already has `nav`, `pos`, `dashboard`, `products`, `orders`, `inventory`, `team` sections. **Verified** for these three files; the agent reports the Settings page is the only translated page.

Reported by the agent, not yet independently re-checked:
- Collapsed sidebar groups open a sub-menu that is hidden in icon mode and have no `tooltip`, so the sidebar is unusable when collapsed.
- Inventory thresholds and reorder settings cannot be saved on their own, because "Apply Adjustment" is disabled when `quantity_change === 0` (the disabled check at `inventory/Index.vue:123` and `:482` is confirmed, the effect on thresholds is the agent's reading).
- The modal's Upgrade button has no handler and "Upgrade Plan" on the dashboard leads to Settings, which has no plan section.
- POS "Clear" wipes the cart with no confirmation or undo.
- Notifications do not link to the thing they describe.
- Employees table clips on mobile (`overflow-hidden`, 8 columns).
- Branch "Delivery" and "Customer Loyalty" toggles are not used by any page.
- Dashboard `max_branches/max_users/max_products` props are never shown.

## Phase 0: Quick wins (about 1 day, no new design)

Ponytail rule: no new abstractions, just fix and label.

- [ ] Sidebar: stop filtering plan-locked items. Render them dimmed with the crown and a hover card (see tooltip catalogue #1). Keep permission and feature filtering.
- [ ] Inventory: compute low/in-stock totals on the server and pass them as props. Allow saving threshold/reorder fields without a quantity change.
- [ ] `UpgradePlanModal`: add a `feature` prop with its own copy ("Receipt Design is on the Enterprise plan"). Format prices with `useCurrency`. Make the Upgrade button do something real or hide it until billing exists.
- [ ] Replace POS `alert()` calls with the toast (Phase 1) or at minimum an inline error.
- [ ] `aria-label` on every icon-only button (about 40: Eye, Edit, Trash, Barcode, back arrows, bell, language, theme, KOT, history, chat). Use one `IconAction` wrapper so a label is required by the type.
- [ ] Employees table: wrap in `overflow-x-auto`.
- [ ] Rows that look like links become links (Orders, Recent Orders, Customers).
- [ ] Dashboard Quick Actions respect permissions.
- [ ] One name for staff everywhere: **Team** (sidebar "Employees", page "Team Members", tab "Users", button "Add User" today).
- [ ] POS and Kitchen open in a new tab: tooltip says so, and "Exit POS" should close or return sensibly instead of loading a second dashboard.

## Phase 1: Shared building blocks

Reuse first. Already installed: `tooltip`, `dialog`, `sheet`, `dropdown-menu`, `tabs`, `skeleton`, `badge`, `card`, `breadcrumb`, `sidebar`, `reka-ui` 2.9, `@vueuse/core`.
Add via shadcn-vue: `hover-card`, `popover`, `command`, `sonner` (toast).

| Component | Replaces | Notes |
| --- | --- | --- |
| `InfoTip` | about 26 copies of `CircleHelp` + `TooltipProvider` boilerplate in products Create/Edit | Trigger is a real `<button aria-label>` so keyboard and screen readers reach it. Props: `text` or default slot. Tap opens it on touch. |
| `IconAction` | icon-only buttons with `title` only | Requires `label`. Renders tooltip + `aria-label`. 44px hit area on touch layouts. |
| `PageHeader` | three header styles | Icon, title, one-line description, actions slot, optional breadcrumb. |
| `StatCard` | 14 hand-built stat rows in two styles | Optional `href` so a card can act as a filter (Low Stock card filters the list). |
| `EmptyState` | about 10 ad-hoc empty blocks | Icon, title, hint, primary action. |
| `StatusBadge` | status colours repeated in Dashboard, Orders, POs, Transfers, Tables, Users | One status-to-colour-and-explanation map. Wrapped in a hover card with the lifecycle. |
| `ConfirmDialog` | about 11 near-identical delete dialogs | Destructive variant, loading state. |
| `DataTableToolbar` | mixed filter-on-submit and filter-as-you-type | Debounced search, consistent selects, date range. Everything filters as you type. |
| Toast (sonner) | `FlashMessage`, POS `alert()` | Queue, `aria-live`, undo action. |
| `PlanGate` | feature-lock use of `UpgradePlanModal` | Names the feature and the plan that unlocks it. |

Acceptance: each page that adopts one deletes its old markup in the same change. No new abstractions without at least three call sites.

## Phase 2: Navigation (the biggest ease-of-use win)

### Sidebar regrouping

Current split mixes daily work with setup. Proposed:

| Group | Items |
| --- | --- |
| Pinned | Dashboard, POS (new tab), Kitchen (new tab) |
| Sell | Orders, Customers, Promotions, Tables |
| Catalog | Products, Categories, Add-ons |
| Stock | Inventory (low-stock count badge), Purchase Orders, Stock Transfers, Suppliers |
| Insights | Reports, AI Insights (locked state if plan-gated) |
| People | Team, Roles, Schedules, Shifts |
| Business | Settings (add a Plan & Billing tab), Branches, Receipt Design |

Rules:
- Every collapsed item has a tooltip. In icon mode a group opens a flyout (`DropdownMenu`, side right) because the inline sub-list is hidden.
- Items hidden by a branch feature toggle show greyed with "Enable in Branches > Feature Settings" instead of vanishing.
- Fix permission oddities the agent found: Customers is gated by `orders.view`, Schedules by `users.edit-role`. Confirm whether that is intended before changing.
- Labels come from the existing `nav.*` keys.

### Header and wayfinding

- Page title in the header, breadcrumbs truncate on mobile.
- Add **Ctrl/Cmd+K command palette** (shadcn `command` on reka-ui, `useMagicKeys`). Version one is client-side only: pages from the same filtered nav arrays the sidebar uses (so permission, plan, and feature rules apply automatically) plus quick actions (Open POS, New product, New purchase order). Typed text jumps to list pages via their existing `?search=` params. Searching actual records needs a new endpoint and is a later step.
- Notification items link to the order or stock item they describe.
- Bell, theme, and language buttons get labels and tooltips. Chat panel becomes `w-[min(380px,100vw-2rem)]` and no longer covers pagination on mobile.
- Reports tab and filters live in the URL so refresh keeps your place.

## Phase 3: Tooltip and hover-card catalogue

Component choice: `InfoTip` (plain tooltip) for one-liners, `hover-card` for structured content, tap-popover on touch. Copy is plain and describes what the person controls, not how the system works.

| # | Where | Element | Copy |
| --- | --- | --- | --- |
| 1 | Sidebar | Locked item (crown) | Hover card: "AI Insights is part of Pro and Enterprise. Upgrade to turn it on." + Upgrade link |
| 2 | Order detail | Void | "Cancels the whole sale and returns stock. Use for mistakes made today." |
| 3 | Order detail | Refund | "Returns money for some or all items. The sale stays on record." |
| 4 | POS | ID Discount | "Senior Citizen or PWD discount. Needs the customer's ID." |
| 5 | POS | Hold Order / Held | "Save this cart and start a new one. Tap Held to bring it back." |
| 6 | POS | (incl.) beside tax | "Prices already include tax." |
| 7 | POS | Split Payment | "Pay with more than one method." |
| 8 | POS | Scan Barcode | "Click here, then scan with your USB scanner." |
| 9 | POS layout | Switch User | "Lock the till so another cashier can sign in with their PIN." |
| 10 | POS | Online payment ("Soon") | Hide it, or say when it arrives. |
| 11 | Inventory | Reorder Point | "When stock falls to this number, a draft purchase order is created." |
| 12 | Inventory | Reorder Quantity | "How many units that purchase order asks for." |
| 13 | Inventory | Adjustment types | One line each: Purchase, Damage, Correction, Initial |
| 14 | Shift detail | Expected Cash | "Starting cash + cash sales - cash refunds." |
| 15 | Shift detail | Difference | "Counted cash minus expected cash. Negative means the drawer is short." |
| 16 | Branches | Each Feature Setting | "Turning this off hides Inventory, Suppliers, Transfers and Purchase Orders." (per toggle, list the pages) |
| 17 | Branch menu | Global / Branch / Effective price | "Effective = the branch price if set, otherwise the global price." |
| 18 | PO / Transfer | Status badges | Hover card showing the lifecycle with the current step highlighted. "Partial: some items received, receive the rest later." |
| 19 | Dashboard | Plan pill | Hover card: usage against limits (the `max_*` props are already sent, e.g. "3 of 5 branches") |
| 20 | Dashboard | "vs yesterday" | "Compared with revenue at this time yesterday." |
| 21 | Dashboard / AI Insights | Beta / "Powered by statistical analysis" | "Insights come from statistics on your own sales data." (Resolve the "AI" naming contradiction.) |
| 22 | Roles | "System" badge | "Built-in role. It cannot be deleted." |
| 23 | Roles | Permissions count | Hover card listing permissions |
| 24 | Team | PIN column | "4-6 digit code used to sign in at the POS." |
| 25 | Reports | Export button | "Exports the current tab for the selected dates." |
| 26 | Reports | Avg Order Value / Forecast | "Revenue divided by orders." / "Estimated from your recent daily averages." |
| 27 | Kitchen | KOT | "Kitchen Order Ticket: the slip the cooks work from." |
| 28 | Promotions | Buy X Get Y, Maximum Discount, Usage Limit | One line each (what Value means, cap for percentage promos, total redemptions across all customers) |
| 29 | Products | Slug | Hide it (developer jargon). Product Type "food": reword "no stock tracking" so owners of packaged food are not misled. |
| 30 | Setup | Branch Code, PIN step | "Short code printed on receipts and order numbers." / why a PIN is needed |

Plus a plain tooltip with `aria-label` on every icon-only action (Phase 0).

## Phase 4: Page-by-page improvements

Do these after Phases 1-2 so each page just adopts the shared parts.

| Page | Main changes |
| --- | --- |
| Dashboard | Usage meter from `max_*` props. Charts follow theme changes. Clickable recent orders. Quick actions by permission. Single card style. |
| Orders | Whole row clickable. Export button. Explain statuses. |
| Products | Stock column. Show plan usage near "Add Product" ("42 of 100"), not only in the upgrade modal. Hide slug. |
| Inventory | Server-side stat counts, clickable Low Stock card to filter, empty state with a real "Add stock" button, threshold editing without an adjustment. |
| Purchase orders / transfers | Progress stepper on the detail page, actions near the top, one create flow (the dialogs and the separate Create/Edit pages have drifted; `promotions/Create.vue` lacks Buy X Get Y). |
| Customers | Replace "With Email" / "With Phone" cards with something actionable (top spenders, new this month). |
| Shifts / schedules | "Shift #id" becomes "Shift, 24 Oct, Maria". Filters work as you type. Large page (860 lines): split it. |
| Reports | Tab and range in URL. Currency from `useCurrency` (currently hardcoded `en-PH`/`PHP`). |
| Branches | Menu & Pricing and Feature Settings become labelled buttons, not icon-only. Hide or mark "Coming soon" for unused toggles. |
| Team / Roles | Search, confirm on deactivate, mobile-safe table, fix "null branch shows the last branch name". |
| Settings | Add Plan & Billing tab (fixes the dead-end Upgrade links). Merge duplicated receipt fields with Receipt Design. Translate the Business Type block. |
| Receipt Design | The form is written twice (mobile and desktop). Build once, lay out responsively. |
| Setup | Label the step dots. Explain Branch Code and PIN. Drop the duplicate PIN prompt later on the dashboard. |
| Welcome-back modal | Add a close button, explain IMP/FIX/NEW, avoid repeating dashboard stats. |

## Phase 5: POS checkout (touch-first, highest daily impact)

- Fewer steps: today a sale passes Complete Purchase, Order Preview, Confirm & Pay, Payment, Complete Order, receipt (about four modals). Merge preview into the payment step.
- **Undo toast** after Clear instead of silent loss.
- Touch targets 44px minimum: quantity +/- (28px now), remove (24-28px), item note, split-payment method buttons, category pills.
- Labels on icon-only buttons (history, category scroll arrows, all X buttons). Distinguish "Discounts" from "ID Discount" (they share the same icon).
- Keyboard shortcuts for the cashier (F-keys: hold, discount, pay, clear, search). No animation on shortcut-triggered actions.
- Shift pill uses `useCurrency`, not a hardcoded peso sign.
- Kitchen: main action and print buttons at least 44px, label the mute toggle.

## Phase 6: i18n

- Wire the sidebar and header to the existing `nav.*` keys first (small, visible win).
- Then add keys page by page as each page adopts shared components. New strings never ship as hardcoded English.
- `fil.json` and `ja.json` mirror `en.json`; keep the three in sync with a small script check in CI (keys present in all three).
- Prerequisite already shipped: the vue-i18n production build fix (`vite.config.ts` alias), otherwise raw keys show in production.

## Phase 7: Consistency and polish

- Delete `glass-card` / gradient-tile variants once pages use `Card` + `StatCard`.
- Standard status colour vocabulary (success, warning, danger, info, neutral) via `StatusBadge`.
- Dark mode pass on every touched page, both modes screenshotted before merge.
- Accessibility pass: contrast 4.5:1, visible focus, 44px touch targets on POS and kitchen, `role="status"` on toasts.

## Non-goals (ponytail: skip until needed)

- No new fonts, no new colour system, no new icon library.
- No landing-page redesign here.
- No global record search in the command palette v1 (needs a new endpoint).
- No decorative motion, no page-load choreography.
- No rewrite of POS state management. Only the checkout flow and touch sizing.
- No Plan & Billing payments. Only the section and honest messaging until billing exists.

## Suggested order and size

| Phase | Size | Risk | Why this order |
| --- | --- | --- | --- |
| 0 Quick wins | 1 day | Low | Fixes real bugs and a11y at once |
| 1 Building blocks | 2-3 days | Low | Everything else reuses them |
| 2 Navigation + command palette | 2-3 days | Medium | Biggest ease-of-use gain |
| 3 Tooltip catalogue | 1-2 days | Low | Mostly copy once `InfoTip` exists |
| 5 POS checkout | 3-4 days | Medium-high | Daily-use screen, test with a real cashier |
| 4 Page-by-page | 4-6 days | Medium | Mechanical once shared parts exist |
| 6 i18n | rolling | Low | Done as each page is touched |
| 7 Polish and QA | 1-2 days | Low | Last |

Ship each phase as its own pull request so it can be reviewed and reverted alone.

## Verification for every phase

- `npm run build` passes (watch for the Windows esbuild out-of-memory error: rerun once before debugging).
- Screens checked in light and dark, at 375, 768, 1024, and 1440px.
- Keyboard-only walkthrough of the changed flow (Tab order, focus visible, Esc closes popups).
- Collapsed-sidebar walkthrough after Phase 2.
- Tooltip check on a touch device or emulation: info is reachable by tap.
- No new hardcoded English strings, no `alert()`, no icon-only button without a label.

## Open questions

1. Should the sidebar permission oddities (Customers needs `orders.view`, Schedules needs `users.edit-role`) be fixed or are they intentional?
2. Billing: is there a plan to take payment in-app soon? That decides whether the Upgrade button links to a real checkout or to a contact form.
3. Are Delivery and Customer Loyalty planned? If not, remove the toggles.
4. Who tests POS changes with real cashiers before release?
5. Run `$impeccable init` now to capture PRODUCT.md (users, tone, brand) so later phases do not re-decide it?
