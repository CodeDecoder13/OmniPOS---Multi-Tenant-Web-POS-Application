<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, CreditCard, LayoutDashboard, LogOut, Ticket, Users } from 'lucide-vue-next';
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

const page = usePage();

const navItems = [
    { title: 'Dashboard', href: '/admin', icon: LayoutDashboard },
    { title: 'Tenants', href: '/admin/tenants', icon: Building2 },
    { title: 'Users', href: '/admin/users', icon: Users },
    { title: 'Plans', href: '/admin/plans', icon: CreditCard },
    { title: 'Promo Codes', href: '/admin/promo-codes', icon: Ticket },
];

function isActive(href: string): boolean {
    const url = page.url;
    if (href === '/admin') {
        return url === '/admin';
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
                        <Link href="/admin">
                            <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-teal-600 text-white">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div class="ml-1 grid flex-1 text-left text-sm">
                                <span class="mb-0.5 truncate leading-tight font-semibold">OmniPOS</span>
                                <span class="truncate text-xs text-muted-foreground">Admin Panel</span>
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
                        <SidebarMenuItem v-for="item in navItems" :key="item.title">
                            <SidebarMenuButton as-child :is-active="isActive(item.href)">
                                <Link :href="item.href">
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
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link href="/admin/logout" method="post" as="button" class="w-full">
                            <LogOut />
                            <span>Logout</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
</template>
