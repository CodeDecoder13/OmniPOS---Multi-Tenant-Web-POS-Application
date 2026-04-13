<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftRight, BarChart3, Building2, CalendarDays, ChefHat, ChevronRight, ClipboardList,
    Clock, Crown, FileText, FolderOpen, LayoutDashboard, LayoutGrid, Package, Puzzle,
    ReceiptText, Settings2, Shield, ShoppingCart, Sparkles, Store, Tag, Truck, UserRound, Users, Warehouse,
} from 'lucide-vue-next';
import type { Component } from 'vue';
import NavUser from '@/components/NavUser.vue';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import { useBranchSettings } from '@/composables/useBranchSettings';
import type { BranchSettings } from '@/composables/useBranchSettings';

interface NavChild {
    title: string;
    path: string;
    icon: Component;
    permission: string | null;
    external?: boolean;
    feature?: keyof BranchSettings;
    plan?: string | string[];
}

interface NavGroup {
    title: string;
    icon: Component;
    children: NavChild[];
}

const page = usePage();
const { tenant, tenantUrl } = useTenant();
const { can } = usePermissions();
const { isEnabled } = useBranchSettings();

const topLevelItems: NavChild[] = [
    { title: 'Dashboard', path: 'dashboard', icon: LayoutDashboard, permission: null },
    { title: 'POS', path: 'pos', icon: ShoppingCart, permission: 'pos.access', external: true, feature: 'pos_enabled' },
    { title: 'Kitchen', path: 'kitchen', icon: ChefHat, permission: 'kitchen.access', external: true, feature: 'kitchen_display' },
];

const operationsGroups: NavGroup[] = [
    {
        title: 'Sales & Orders',
        icon: ClipboardList,
        children: [
            { title: 'Orders', path: 'orders', icon: ClipboardList, permission: 'orders.view' },
            { title: 'Customers', path: 'customers', icon: UserRound, permission: 'orders.view' },
            { title: 'Shifts', path: 'shifts', icon: Clock, permission: 'shifts.view' },
            { title: 'Reports', path: 'reports', icon: BarChart3, permission: 'reports.view' },
            { title: 'AI Insights', path: 'ai-insights', icon: Sparkles, permission: 'reports.view', plan: ['pro', 'enterprise'] },
        ],
    },
    {
        title: 'Products & Menu',
        icon: Package,
        children: [
            { title: 'Categories', path: 'categories', icon: FolderOpen, permission: 'products.view' },
            { title: 'Products', path: 'products', icon: Package, permission: 'products.view' },
            { title: 'Add-ons', path: 'addons', icon: Puzzle, permission: 'products.view' },
            { title: 'Tables', path: 'tables', icon: LayoutGrid, permission: 'tables.view', feature: 'dine_in' },
            { title: 'Promotions', path: 'promotions', icon: Tag, permission: 'promotions.view' },
        ],
    },
    {
        title: 'Inventory & Supply',
        icon: Warehouse,
        children: [
            { title: 'Inventory', path: 'inventory', icon: Warehouse, permission: 'inventory.view', feature: 'inventory_tracking' },
            { title: 'Suppliers', path: 'suppliers', icon: Truck, permission: 'suppliers.view', feature: 'inventory_tracking' },
            { title: 'Stock Transfers', path: 'stock-transfers', icon: ArrowLeftRight, permission: 'inventory.view', feature: 'inventory_tracking' },
            { title: 'Purchase Orders', path: 'purchase-orders', icon: FileText, permission: 'inventory.view', feature: 'inventory_tracking' },
        ],
    },
];

const storeGroups: NavGroup[] = [
    {
        title: 'Team',
        icon: Users,
        children: [
            { title: 'Employees', path: 'users', icon: Users, permission: 'users.view' },
            { title: 'Roles', path: 'roles', icon: Shield, permission: 'roles.view' },
            { title: 'Schedules', path: 'shift-schedules', icon: CalendarDays, permission: 'users.edit-role' },
        ],
    },
    {
        title: 'Store',
        icon: Store,
        children: [
            { title: 'Branches', path: 'branches', icon: Building2, permission: 'branches.view' },
            { title: 'Settings', path: 'settings', icon: Settings2, permission: 'settings.manage' },
            { title: 'Receipt Design', path: 'receipt-customization', icon: ReceiptText, permission: 'settings.manage', plan: 'enterprise' },
        ],
    },
];

function isActive(path: string): boolean {
    const url = page.url;
    const href = tenantUrl(path);
    if (path === 'dashboard') {
        return url === href;
    }
    return url.startsWith(href);
}

function hasActiveChild(group: NavGroup): boolean {
    return group.children.some((child) => isActive(child.path));
}

function matchesPlan(plan: string | string[], slug: string | undefined): boolean {
    if (!slug) return false;
    return Array.isArray(plan) ? plan.includes(slug) : slug === plan;
}

function filterByPermission(items: NavChild[]): NavChild[] {
    return items.filter((item) => {
        if (item.permission && !can(item.permission)) return false;
        if (item.feature && !isEnabled(item.feature)) return false;
        if (item.plan && !matchesPlan(item.plan, tenant.value?.subscription?.plan?.slug)) return false;
        return true;
    });
}

function requiresUpgrade(item: NavChild): boolean {
    if (!item.plan) return false;
    return !matchesPlan(item.plan, tenant.value?.subscription?.plan?.slug);
}

const filteredTopLevel = computed(() => filterByPermission(topLevelItems));

const filteredOperationsGroups = computed(() =>
    operationsGroups
        .map((group) => ({
            ...group,
            children: filterByPermission(group.children),
        }))
        .filter((group) => group.children.length > 0),
);

const filteredStoreGroups = computed(() =>
    storeGroups
        .map((group) => ({
            ...group,
            children: filterByPermission(group.children),
        }))
        .filter((group) => group.children.length > 0),
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="tenantUrl('dashboard')">
                            <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-gradient-to-br from-teal-400 to-teal-600 text-white shadow-lg shadow-teal-500/25">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="ml-1 grid flex-1 text-left text-sm">
                                <span class="mb-0.5 truncate leading-tight font-semibold">{{ tenant?.name }}</span>
                                <Badge v-if="tenant?.subscription?.plan" variant="outline" class="w-fit text-[10px] px-1.5 py-0">
                                    {{ tenant.subscription.plan.name }}
                                </Badge>
                                <span v-else class="truncate text-xs text-muted-foreground">No Plan</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="overflow-x-hidden">
            <!-- Top-level items -->
            <SidebarGroup>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in filteredTopLevel" :key="item.title">
                            <SidebarMenuButton as-child :is-active="isActive(item.path)">
                                <a v-if="item.external" :href="tenantUrl(item.path)" target="_blank">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </a>
                                <Link v-else :href="tenantUrl(item.path)">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarSeparator />

            <!-- Operations groups -->
            <SidebarGroup>
                <SidebarGroupLabel>Operations</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <Collapsible
                            v-for="group in filteredOperationsGroups"
                            :key="group.title"
                            as-child
                            :default-open="hasActiveChild(group)"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger as-child>
                                    <SidebarMenuButton>
                                        <component :is="group.icon" />
                                        <span>{{ group.title }}</span>
                                        <ChevronRight class="ml-auto size-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        <SidebarMenuSubItem v-for="child in group.children" :key="child.title">
                                            <SidebarMenuSubButton as-child size="sm" :is-active="isActive(child.path)">
                                                <Link :href="tenantUrl(child.path)">
                                                    <span>{{ child.title }}</span>
                                                    <Crown
                                                        v-if="requiresUpgrade(child)"
                                                        class="ml-auto h-3 w-3 shrink-0 text-amber-500"
                                                    />
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarSeparator />

            <!-- Store groups -->
            <SidebarGroup>
                <SidebarGroupLabel>Store</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <Collapsible
                            v-for="group in filteredStoreGroups"
                            :key="group.title"
                            as-child
                            :default-open="hasActiveChild(group)"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger as-child>
                                    <SidebarMenuButton>
                                        <component :is="group.icon" />
                                        <span>{{ group.title }}</span>
                                        <ChevronRight class="ml-auto size-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        <SidebarMenuSubItem v-for="child in group.children" :key="child.title">
                                            <SidebarMenuSubButton as-child size="sm" :is-active="isActive(child.path)">
                                                <Link :href="tenantUrl(child.path)">
                                                    <span>{{ child.title }}</span>
                                                    <Crown
                                                        v-if="requiresUpgrade(child)"
                                                        class="ml-auto h-3 w-3 shrink-0 text-amber-500"
                                                    />
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
