<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Building2, FolderOpen, Package, Plus, Shield, ShoppingCart, UserPlus, Users } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useTenant } from '@/composables/useTenant';

const props = defineProps<{
    stats: {
        branches_count: number;
        active_branches_count: number;
        users_count: number;
        roles_count: number;
        categories_count: number;
        products_count: number;
        plan_name: string;
        subscription_status: string;
        max_branches: number | null;
        max_users: number | null;
        max_products: number | null;
    };
}>();

const page = usePage();
const { tenant, tenantUrl } = useTenant();

const user = page.props.auth.user as { name: string };
const firstName = user.name.split(' ')[0];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
];

const statCards = [
    {
        title: 'Branches',
        value: props.stats.branches_count,
        subtitle: props.stats.max_branches ? `of ${props.stats.max_branches} max` : 'Unlimited',
        icon: Building2,
        color: 'text-teal-600 bg-teal-100 dark:bg-teal-900/30',
    },
    {
        title: 'Team Members',
        value: props.stats.users_count,
        subtitle: props.stats.max_users ? `of ${props.stats.max_users} max` : 'Unlimited',
        icon: Users,
        color: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30',
    },
    {
        title: 'Roles',
        value: props.stats.roles_count,
        subtitle: 'Defined roles',
        icon: Shield,
        color: 'text-indigo-600 bg-indigo-100 dark:bg-indigo-900/30',
    },
    {
        title: 'Categories',
        value: props.stats.categories_count,
        subtitle: 'Product categories',
        icon: FolderOpen,
        color: 'text-amber-600 bg-amber-100 dark:bg-amber-900/30',
    },
    {
        title: 'Products',
        value: props.stats.products_count,
        subtitle: props.stats.max_products ? `of ${props.stats.max_products} max` : 'Unlimited',
        icon: Package,
        color: 'text-orange-600 bg-orange-100 dark:bg-orange-900/30',
    },
    {
        title: 'Plan',
        value: props.stats.plan_name,
        subtitle: 'Current plan',
        icon: Package,
        color: 'text-purple-600 bg-purple-100 dark:bg-purple-900/30',
    },
    {
        title: 'Status',
        value: props.stats.subscription_status === 'active' ? 'Active' : props.stats.subscription_status,
        subtitle: 'Subscription',
        icon: ShoppingCart,
        color: props.stats.subscription_status === 'active'
            ? 'text-green-600 bg-green-100 dark:bg-green-900/30'
            : 'text-amber-600 bg-amber-100 dark:bg-amber-900/30',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-bold">Welcome back, {{ firstName }}!</h1>
                <p class="text-muted-foreground">Here's an overview of your organization.</p>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="card in statCards"
                    :key="card.title"
                    class="rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ card.title }}</p>
                            <p class="mt-1 text-2xl font-bold">{{ card.value }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ card.subtitle }}</p>
                        </div>
                        <div class="rounded-lg p-3" :class="card.color">
                            <component :is="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div>
                <h2 class="mb-4 text-lg font-semibold">Quick Actions</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        :href="tenantUrl('branches/create')"
                        class="flex items-center gap-3 rounded-xl border bg-white p-4 shadow-sm transition hover:border-teal-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:hover:border-teal-700"
                    >
                        <div class="rounded-lg bg-teal-100 p-2.5 dark:bg-teal-900/30">
                            <Plus class="h-5 w-5 text-teal-600" />
                        </div>
                        <div>
                            <p class="font-medium">Add Branch</p>
                            <p class="text-xs text-muted-foreground">Create a new branch</p>
                        </div>
                    </Link>

                    <Link
                        :href="tenantUrl('users')"
                        class="flex items-center gap-3 rounded-xl border bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:hover:border-blue-700"
                    >
                        <div class="rounded-lg bg-blue-100 p-2.5 dark:bg-blue-900/30">
                            <UserPlus class="h-5 w-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="font-medium">Invite User</p>
                            <p class="text-xs text-muted-foreground">Add team member</p>
                        </div>
                    </Link>

                    <Link
                        :href="tenantUrl('products/create')"
                        class="flex items-center gap-3 rounded-xl border bg-white p-4 shadow-sm transition hover:border-orange-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:hover:border-orange-700"
                    >
                        <div class="rounded-lg bg-orange-100 p-2.5 dark:bg-orange-900/30">
                            <Package class="h-5 w-5 text-orange-600" />
                        </div>
                        <div>
                            <p class="font-medium">Add Product</p>
                            <p class="text-xs text-muted-foreground">Create a new product</p>
                        </div>
                    </Link>

                    <a
                        :href="tenantUrl('pos')"
                        target="_blank"
                        class="flex items-center gap-3 rounded-xl border bg-white p-4 shadow-sm transition hover:border-green-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:hover:border-green-700"
                    >
                        <div class="rounded-lg bg-green-100 p-2.5 dark:bg-green-900/30">
                            <ShoppingCart class="h-5 w-5 text-green-600" />
                        </div>
                        <div>
                            <p class="font-medium">Open POS</p>
                            <p class="text-xs text-muted-foreground">Start selling</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
