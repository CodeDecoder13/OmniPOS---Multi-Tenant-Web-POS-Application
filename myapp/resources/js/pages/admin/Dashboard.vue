<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Building2, DollarSign, TrendingUp, Users } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    stats: {
        total_tenants: number;
        active_tenants: number;
        total_users: number;
        total_revenue: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
];

const cards = [
    {
        title: 'Total Tenants',
        value: props.stats.total_tenants,
        icon: Building2,
        color: 'text-teal-600 bg-teal-100 dark:bg-teal-900/30',
    },
    {
        title: 'Active Tenants',
        value: props.stats.active_tenants,
        icon: TrendingUp,
        color: 'text-green-600 bg-green-100 dark:bg-green-900/30',
    },
    {
        title: 'Total Users',
        value: props.stats.total_users,
        icon: Users,
        color: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30',
    },
    {
        title: 'Monthly Revenue',
        value: `₱${Number(props.stats.total_revenue).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`,
        icon: DollarSign,
        color: 'text-amber-600 bg-amber-100 dark:bg-amber-900/30',
    },
];
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">Dashboard</h1>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="card in cards"
                    :key="card.title"
                    class="rounded-xl border bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">{{ card.title }}</p>
                            <p class="mt-1 text-2xl font-bold">{{ card.value }}</p>
                        </div>
                        <div class="rounded-lg p-3" :class="card.color">
                            <component :is="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
