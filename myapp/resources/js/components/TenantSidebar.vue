<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart3, Building2, ClipboardList, Clock, FolderOpen, LayoutDashboard, Package, Settings, Shield, ShoppingCart, UserRound, Users, Warehouse } from 'lucide-vue-next';
import NavUser from '@/components/NavUser.vue';
import { Badge } from '@/components/ui/badge';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarGroupContent,
} from '@/components/ui/sidebar';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';

const page = usePage();
const { tenant, tenantUrl } = useTenant();
const { can } = usePermissions();

const mainNav = [
    { title: 'Dashboard', path: 'dashboard', icon: LayoutDashboard, permission: null },
    { title: 'POS', path: 'pos', icon: ShoppingCart, permission: 'pos.access' },
    { title: 'Orders', path: 'orders', icon: ClipboardList, permission: 'orders.view' },
    { title: 'Shifts', path: 'shifts', icon: Clock, permission: 'shifts.view' },
    { title: 'Reports', path: 'reports', icon: BarChart3, permission: 'reports.view' },
    { title: 'Customers', path: 'customers', icon: UserRound, permission: 'orders.view' },
    { title: 'Branches', path: 'branches', icon: Building2, permission: 'branches.view' },
    { title: 'Users', path: 'users', icon: Users, permission: 'users.view' },
    { title: 'Roles', path: 'roles', icon: Shield, permission: 'roles.view' },
    { title: 'Categories', path: 'categories', icon: FolderOpen, permission: 'products.view' },
    { title: 'Products', path: 'products', icon: Package, permission: 'products.view' },
    { title: 'Inventory', path: 'inventory', icon: Warehouse, permission: 'inventory.view' },
];

const systemNav = [
    { title: 'Settings', path: 'settings', icon: Settings, permission: 'settings.manage' },
];

const filteredNav = mainNav.filter((item) => !item.permission || can(item.permission));
const filteredSystemNav = systemNav.filter((item) => !item.permission || can(item.permission));

function isActive(path: string): boolean {
    const url = page.url;
    const href = tenantUrl(path);
    if (path === 'dashboard') {
        return url === href;
    }
    return url.startsWith(href);
}
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

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel>Navigation</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in filteredNav" :key="item.title">
                            <SidebarMenuButton as-child :is-active="isActive(item.path)">
                                <a v-if="item.path === 'pos'" :href="tenantUrl(item.path)" target="_blank">
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

            <SidebarGroup v-if="filteredSystemNav.length > 0">
                <SidebarGroupLabel>System</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in filteredSystemNav" :key="item.title">
                            <SidebarMenuButton as-child :is-active="isActive(item.path)">
                                <Link :href="tenantUrl(item.path)">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
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
