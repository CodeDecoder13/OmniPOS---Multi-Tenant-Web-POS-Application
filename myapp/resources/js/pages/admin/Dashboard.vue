<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, DollarSign, TrendingDown, TrendingUp, Users, UserPlus, Clock, BarChart3 } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { AdminActivityLog, BreadcrumbItem } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        total_tenants: number;
        active_tenants: number;
        total_users: number;
        mrr: number;
        trial_tenants: number;
        new_signups: number;
        churn_rate: number;
        avg_revenue: number;
    };
    revenueTrend: { labels: string[]; data: number[] };
    planDistribution: { name: string; count: number }[];
    recentActivity: AdminActivityLog[];
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
        title: 'MRR',
        value: `₱${Number(props.stats.mrr).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`,
        icon: DollarSign,
        color: 'text-amber-600 bg-amber-100 dark:bg-amber-900/30',
    },
    {
        title: 'New Signups (Month)',
        value: props.stats.new_signups,
        icon: UserPlus,
        color: 'text-purple-600 bg-purple-100 dark:bg-purple-900/30',
    },
    {
        title: 'Trial Tenants',
        value: props.stats.trial_tenants,
        icon: Clock,
        color: 'text-indigo-600 bg-indigo-100 dark:bg-indigo-900/30',
    },
    {
        title: 'Churn Rate',
        value: `${props.stats.churn_rate}%`,
        icon: TrendingDown,
        color: 'text-red-600 bg-red-100 dark:bg-red-900/30',
    },
    {
        title: 'Avg Revenue/Tenant',
        value: `₱${Number(props.stats.avg_revenue).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`,
        icon: BarChart3,
        color: 'text-cyan-600 bg-cyan-100 dark:bg-cyan-900/30',
    },
];

const revenueChartOptions = computed(() => ({
    chart: { type: 'area' as const, toolbar: { show: false }, fontFamily: 'inherit' },
    xaxis: { categories: props.revenueTrend.labels },
    yaxis: { labels: { formatter: (val: number) => `₱${val.toLocaleString()}` } },
    stroke: { curve: 'smooth' as const, width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
    colors: ['#0d9488'],
    dataLabels: { enabled: false },
    tooltip: { y: { formatter: (val: number) => `₱${val.toLocaleString('en-PH', { minimumFractionDigits: 2 })}` } },
}));

const revenueSeries = computed(() => ([
    { name: 'Revenue', data: props.revenueTrend.data },
]));

const planChartOptions = computed(() => ({
    chart: { type: 'donut' as const, fontFamily: 'inherit' },
    labels: props.planDistribution.map(p => p.name),
    colors: ['#0d9488', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
    legend: { position: 'bottom' as const },
}));

const planSeries = computed(() => props.planDistribution.map(p => p.count));

function formatAction(action: string): string {
    return action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h ago`;
    return `${Math.floor(hours / 24)}d ago`;
}
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">Dashboard</h1>

            <!-- Stats Grid -->
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

            <!-- Charts Row -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Revenue Trend -->
                <div class="lg:col-span-2 rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Revenue Trend</h2>
                        <Link href="/admin/reports/revenue" class="text-sm text-teal-600 hover:underline">View Report</Link>
                    </div>
                    <apexchart type="area" height="280" :options="revenueChartOptions" :series="revenueSeries" />
                </div>

                <!-- Plan Distribution -->
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Plan Distribution</h2>
                        <Link href="/admin/reports/subscriptions" class="text-sm text-teal-600 hover:underline">View Report</Link>
                    </div>
                    <apexchart v-if="planSeries.some(v => v > 0)" type="donut" height="280" :options="planChartOptions" :series="planSeries" />
                    <div v-else class="flex h-[280px] items-center justify-center text-sm text-muted-foreground">No subscription data</div>
                </div>
            </div>

            <!-- Recent Activity & Quick Actions -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Recent Activity -->
                <div class="lg:col-span-2 rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Recent Activity</h2>
                        <Link href="/admin/activity-log" class="text-sm text-teal-600 hover:underline">View All</Link>
                    </div>
                    <div v-if="recentActivity.length > 0" class="space-y-3">
                        <div
                            v-for="log in recentActivity"
                            :key="log.id"
                            class="flex items-center justify-between rounded-lg border px-4 py-2.5 dark:border-gray-800"
                        >
                            <div>
                                <span class="font-medium">{{ log.admin?.name ?? 'System' }}</span>
                                <span class="text-muted-foreground"> {{ formatAction(log.action) }}</span>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ timeAgo(log.created_at) }}</span>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground">No recent activity.</p>
                </div>

                <!-- Quick Actions -->
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Quick Actions</h2>
                    <div class="flex flex-col gap-2">
                        <Link href="/admin/tenants/create" class="rounded-lg border px-4 py-2.5 text-sm font-medium hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800">
                            + New Tenant
                        </Link>
                        <Link href="/admin/users/create" class="rounded-lg border px-4 py-2.5 text-sm font-medium hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800">
                            + New User
                        </Link>
                        <Link href="/admin/plans/create" class="rounded-lg border px-4 py-2.5 text-sm font-medium hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800">
                            + New Plan
                        </Link>
                        <Link href="/admin/promo-codes/create" class="rounded-lg border px-4 py-2.5 text-sm font-medium hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800">
                            + New Promo Code
                        </Link>
                        <Link href="/admin/reports/revenue" class="rounded-lg border px-4 py-2.5 text-sm font-medium hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800">
                            View Reports
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
