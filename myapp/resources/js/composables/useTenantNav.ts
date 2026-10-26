import { usePage } from '@inertiajs/vue3';
import {
    ArrowLeftRight, BadgePercent, BarChart3, Building2, CalendarDays, ChefHat, ClipboardList, Clock, FileText,
    FolderOpen, LayoutDashboard, LayoutGrid, Package, Puzzle, ReceiptText, Settings2, Shield,
    ShoppingCart, Sparkles, Tag, Truck, UserCheck, UserRound, Users, Warehouse,
} from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';
import { useI18n } from 'vue-i18n';
import { useBranchSettings } from '@/composables/useBranchSettings';
import type { BranchSettings } from '@/composables/useBranchSettings';
import { usePermissions } from '@/composables/usePermissions';
import { useTenant } from '@/composables/useTenant';

/**
 * ok       - usable
 * locked   - the tenant's plan doesn't include it (shown with a crown, opens an upgrade prompt)
 * disabled - turned off in this branch's Feature Settings (shown dimmed with a hint)
 */
export type NavState = 'ok' | 'locked' | 'disabled';

export interface NavItem {
    key: string;
    label: string;
    path: string;
    icon: Component;
    external: boolean;
    state: NavState;
    /** Plans that unlock a locked item, e.g. ['pro', 'enterprise']. */
    plans: string[];
}

export interface NavSection {
    key: string;
    title: string;
    items: NavItem[];
}

interface NavDef {
    key: string;
    path: string;
    icon: Component;
    permission: string | null;
    external?: boolean;
    feature?: keyof BranchSettings;
    plan?: string[];
}

// One flat definition. Section titles and item labels come from nav.* translations.
const defs: { key: string; items: NavDef[] }[] = [
    {
        key: 'pinned',
        items: [
            { key: 'dashboard', path: 'dashboard', icon: LayoutDashboard, permission: null },
            { key: 'pos', path: 'pos', icon: ShoppingCart, permission: 'pos.access', external: true, feature: 'pos_enabled' },
            { key: 'kitchen', path: 'kitchen', icon: ChefHat, permission: 'kitchen.access', external: true, feature: 'kitchen_display' },
        ],
    },
    {
        key: 'sell',
        items: [
            { key: 'orders', path: 'orders', icon: ClipboardList, permission: 'orders.view' },
            { key: 'customers', path: 'customers', icon: UserRound, permission: 'orders.view' },
            { key: 'promotions', path: 'promotions', icon: Tag, permission: 'promotions.view' },
            { key: 'tables', path: 'tables', icon: LayoutGrid, permission: 'tables.view', feature: 'dine_in' },
        ],
    },
    {
        key: 'catalog',
        items: [
            { key: 'products', path: 'products', icon: Package, permission: 'products.view' },
            { key: 'categories', path: 'categories', icon: FolderOpen, permission: 'products.view' },
            { key: 'addons', path: 'addons', icon: Puzzle, permission: 'products.view' },
        ],
    },
    {
        key: 'stock',
        items: [
            { key: 'inventory', path: 'inventory', icon: Warehouse, permission: 'inventory.view', feature: 'inventory_tracking' },
            { key: 'purchaseOrders', path: 'purchase-orders', icon: FileText, permission: 'inventory.view', feature: 'inventory_tracking' },
            { key: 'stockTransfers', path: 'stock-transfers', icon: ArrowLeftRight, permission: 'inventory.view', feature: 'inventory_tracking' },
            { key: 'suppliers', path: 'suppliers', icon: Truck, permission: 'suppliers.view', feature: 'inventory_tracking' },
        ],
    },
    {
        key: 'insights',
        items: [
            { key: 'reports', path: 'reports', icon: BarChart3, permission: 'reports.view' },
            { key: 'aiInsights', path: 'ai-insights', icon: Sparkles, permission: 'reports.view', plan: ['pro', 'enterprise'] },
        ],
    },
    {
        key: 'people',
        items: [
            { key: 'members', path: 'users', icon: Users, permission: 'users.view' },
            { key: 'roles', path: 'roles', icon: Shield, permission: 'roles.view' },
            { key: 'staff', path: 'staff', icon: UserCheck, permission: 'staff.manage', feature: 'staff_tracking' },
            { key: 'commissions', path: 'staff/commissions', icon: BadgePercent, permission: 'staff.report', feature: 'commissions' },
            { key: 'shifts', path: 'shifts', icon: Clock, permission: 'shifts.view' },
            { key: 'schedules', path: 'shift-schedules', icon: CalendarDays, permission: 'users.edit-role' },
        ],
    },
    {
        key: 'business',
        items: [
            { key: 'settings', path: 'settings', icon: Settings2, permission: 'settings.manage' },
            { key: 'branches', path: 'branches', icon: Building2, permission: 'branches.view' },
            { key: 'receiptDesign', path: 'receipt-customization', icon: ReceiptText, permission: 'settings.manage', plan: ['enterprise'] },
        ],
    },
];

/** Sidebar and command palette share this, so both follow the same permission, plan and feature rules. */
export function useTenantNav() {
    const { t } = useI18n();
    const { can } = usePermissions();
    const { isEnabled } = useBranchSettings();
    const { tenant, tenantUrl } = useTenant();
    const page = usePage();
    const terms = computed(() => (page.props.businessProfile as { terms?: { staff_plural?: string } } | null)?.terms);

    const sections = computed<NavSection[]>(() => {
        const planSlug = tenant.value?.subscription?.plan?.slug;

        return defs
            .map((section) => ({
                key: section.key,
                title: section.key === 'pinned' ? '' : t(`nav.${section.key}`),
                items: section.items
                    .filter((d) => !d.permission || can(d.permission))
                    .map<NavItem>((d) => {
                        let state: NavState = 'ok';
                        if (d.plan && (!planSlug || !d.plan.includes(planSlug))) state = 'locked';
                        else if (d.feature && !isEnabled(d.feature)) state = 'disabled';

                        return {
                            key: d.key,
                            // The staff page is called what this business calls them: Stylists, Barbers, Technicians...
                            label: d.key === 'staff' ? (terms.value?.staff_plural ?? t('nav.staff')) : t(`nav.${d.key}`),
                            path: d.path,
                            icon: d.icon,
                            external: !!d.external,
                            state,
                            plans: d.plan ?? [],
                        };
                    }),
            }))
            .filter((s) => s.items.length > 0);
    });

    function isActive(path: string): boolean {
        const href = tenantUrl(path);
        // 'staff' must not also light up on its child page, staff/commissions.
        const exact = path === 'dashboard' || path === 'staff';
        const url = page.url.split('?')[0];

        return exact ? url === href : page.url.startsWith(href);
    }

    return { sections, isActive, tenantUrl, can };
}
