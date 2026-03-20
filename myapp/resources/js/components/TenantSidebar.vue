<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftRight, BarChart3, Building2, CalendarDays, ChefHat, ChevronRight, ClipboardList,
    Clock, FileText, FolderOpen, LayoutDashboard, LayoutGrid, Package, Puzzle,
    Settings, Shield, ShoppingCart, Tag, Truck, UserRound, Users, Warehouse,
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
import { useI18n } from 'vue-i18n';
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
}

interface NavGroup {
    title: string;
    icon: Component;
    children: NavChild[];
}

const { t } = useI18n();
const page = usePage();
const { tenant, tenantUrl } = useTenant();
const { can } = usePermissions();
const { isEnabled } = useBranchSettings();

const topLevelItems: NavChild[] = [
    { title: 'nav.dashboard', path: 'dashboard', icon: LayoutDashboard, permission: null },
    { title: 'nav.pos', path: 'pos', icon: ShoppingCart, permission: 'pos.access', external: true, feature: 'pos_enabled' },
    { title: 'nav.kitchen', path: 'kitchen', icon: ChefHat, permission: 'kitchen.access', external: true, feature: 'kitchen_display' },
];

const navGroups: NavGroup[] = [
    {
        title: 'nav.salesOrders',
        icon: ClipboardList,
        children: [
            { title: 'nav.orders', path: 'orders', icon: ClipboardList, permission: 'orders.view' },
            { title: 'nav.customers', path: 'customers', icon: UserRound, permission: 'orders.view' },
            { title: 'nav.shifts', path: 'shifts', icon: Clock, permission: 'shifts.view' },
            { title: 'nav.reports', path: 'reports', icon: BarChart3, permission: 'reports.view' },
        ],
    },
    {
        title: 'nav.productsMenu',
        icon: Package,
        children: [
            { title: 'nav.categories', path: 'categories', icon: FolderOpen, permission: 'products.view' },
            { title: 'nav.products', path: 'products', icon: Package, permission: 'products.view' },
            { title: 'nav.addons', path: 'addons', icon: Puzzle, permission: 'products.view' },
            { title: 'nav.tables', path: 'tables', icon: LayoutGrid, permission: 'tables.view', feature: 'dine_in' },
            { title: 'nav.promotions', path: 'promotions', icon: Tag, permission: 'promotions.view' },
        ],
    },
    {
        title: 'nav.inventorySupply',
        icon: Warehouse,
        children: [
            { title: 'nav.inventory', path: 'inventory', icon: Warehouse, permission: 'inventory.view', feature: 'inventory_tracking' },
            { title: 'nav.suppliers', path: 'suppliers', icon: Truck, permission: 'suppliers.view', feature: 'inventory_tracking' },
            { title: 'nav.stockTransfers', path: 'stock-transfers', icon: ArrowLeftRight, permission: 'inventory.view', feature: 'inventory_tracking' },
            { title: 'nav.purchaseOrders', path: 'purchase-orders', icon: FileText, permission: 'inventory.view', feature: 'inventory_tracking' },
        ],
    },
    {
        title: 'nav.team',
        icon: Users,
        children: [
            { title: 'nav.users', path: 'users', icon: Users, permission: 'users.view' },
            { title: 'nav.roles', path: 'roles', icon: Shield, permission: 'roles.view' },
            { title: 'nav.schedules', path: 'shift-schedules', icon: CalendarDays, permission: 'users.edit-role' },
            { title: 'nav.branches', path: 'branches', icon: Building2, permission: 'branches.view' },
        ],
    },
];

const systemNav: NavChild[] = [
    { title: 'nav.settings', path: 'settings', icon: Settings, permission: 'settings.manage' },
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

function filterByPermission(items: NavChild[]): NavChild[] {
    return items.filter((item) => {
        if (item.permission && !can(item.permission)) return false;
        if (item.feature && !isEnabled(item.feature)) return false;
        return true;
    });
}

const filteredTopLevel = computed(() => filterByPermission(topLevelItems));
const filteredSystemNav = computed(() => filterByPermission(systemNav));

const filteredGroups = computed(() =>
    navGroups
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
                            <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-teal-600 text-white">
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
                                    <span>{{ $t(item.title) }}</span>
                                </a>
                                <Link v-else :href="tenantUrl(item.path)">
                                    <component :is="item.icon" />
                                    <span>{{ $t(item.title) }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarSeparator />

            <!-- Collapsible groups — all in one group, no redundant labels -->
            <SidebarGroup>
                <SidebarGroupLabel>{{ $t('nav.manage') }}</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <Collapsible
                            v-for="group in filteredGroups"
                            :key="group.title"
                            as-child
                            :default-open="hasActiveChild(group)"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger as-child>
                                    <SidebarMenuButton>
                                        <component :is="group.icon" />
                                        <span>{{ $t(group.title) }}</span>
                                        <ChevronRight class="ml-auto size-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        <SidebarMenuSubItem v-for="child in group.children" :key="child.title">
                                            <SidebarMenuSubButton as-child size="sm" :is-active="isActive(child.path)">
                                                <Link :href="tenantUrl(child.path)">
                                                    <span>{{ $t(child.title) }}</span>
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

            <!-- System -->
            <SidebarGroup v-if="filteredSystemNav.length > 0" class="mt-auto">
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in filteredSystemNav" :key="item.title">
                            <SidebarMenuButton as-child :is-active="isActive(item.path)">
                                <Link :href="tenantUrl(item.path)">
                                    <component :is="item.icon" />
                                    <span>{{ $t(item.title) }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
