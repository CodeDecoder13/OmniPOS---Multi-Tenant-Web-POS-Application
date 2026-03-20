<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Building2, FolderOpen, Package, Plus, Shield, ShoppingCart,
    UserPlus, Users, TrendingUp, TrendingDown, DollarSign,
    ArrowUpRight, CreditCard, BarChart3,
} from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
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
    todayRevenue: number;
    todayOrderCount: number;
    yesterdayRevenue: number;
    salesTrend: { date: string; day: string; revenue: number; orders: number }[];
    ordersByStatus: Record<string, number>;
    paymentsByMethod: Record<string, number>;
    topProducts: { name: string; qty: number; revenue: number }[];
    recentOrders: { id: number; order_number: string; total: number; status: string; branch: string; created_at: string }[];
}>();

const { t } = useI18n();
const page = usePage();
const { tenantUrl } = useTenant();

const user = page.props.auth.user as { name: string };
const firstName = user.name.split(' ')[0];

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.dashboard'), href: tenantUrl('dashboard') },
]);

const revenueChange = computed(() => {
    if (props.yesterdayRevenue === 0) return props.todayRevenue > 0 ? 100 : 0;
    return Math.round(((props.todayRevenue - props.yesterdayRevenue) / props.yesterdayRevenue) * 100);
});

function formatCurrency(val: number) {
    return '₱' + val.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Line chart - Sales Trend
const lineChartOptions = computed(() => ({
    chart: {
        type: 'area' as const,
        height: 256,
        toolbar: { show: false },
        fontFamily: 'inherit',
        sparkline: { enabled: false },
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 600,
            animateGradually: { enabled: true, delay: 100 },
        },
    },
    colors: ['#0d9488'],
    stroke: { width: 3, curve: 'smooth' as const },
    fill: {
        type: 'gradient',
        gradient: {
            shade: 'light',
            type: 'vertical',
            shadeIntensity: 0.15,
            opacityFrom: 0.3,
            opacityTo: 0.05,
            stops: [0, 90, 100],
        },
    },
    xaxis: {
        categories: props.salesTrend.map(d => d.day),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#9ca3af', fontSize: '11px' } },
    },
    yaxis: {
        labels: {
            style: { colors: '#9ca3af', fontSize: '11px' },
            formatter: (val: number) => '₱' + (val >= 1000 ? (val / 1000).toFixed(0) + 'k' : val),
        },
    },
    grid: {
        borderColor: 'rgba(156, 163, 175, 0.1)',
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
    },
    markers: {
        size: 4,
        colors: ['#0d9488'],
        strokeColors: '#fff',
        strokeWidth: 2,
        hover: { size: 6 },
    },
    dataLabels: { enabled: false },
    legend: { show: false },
    tooltip: {
        theme: 'dark',
        style: { fontSize: '12px' },
        y: {
            formatter: (val: number) => '₱' + val.toLocaleString('en-PH', { minimumFractionDigits: 2 }),
        },
    },
}));

const lineSeries = computed(() => [{
    name: 'Revenue',
    data: props.salesTrend.map(d => d.revenue),
}]);

// Order status doughnut
const statusLabels: Record<string, string> = {
    completed: 'Completed',
    pending: 'Pending',
    voided: 'Voided',
    refunded: 'Refunded',
};
const statusColors: Record<string, string> = {
    completed: '#0d9488',
    pending: '#f59e0b',
    voided: '#ef4444',
    refunded: '#8b5cf6',
};

const orderStatusEntries = computed(() => Object.entries(props.ordersByStatus));
const orderStatusSeries = computed(() => orderStatusEntries.value.map(([, v]) => v));
const orderStatusOptions = computed(() => ({
    chart: {
        type: 'donut' as const,
        fontFamily: 'inherit',
        animations: { enabled: true, easing: 'easeinout', speed: 600 },
    },
    colors: orderStatusEntries.value.map(([k]) => statusColors[k] || '#9ca3af'),
    labels: orderStatusEntries.value.map(([k]) => statusLabels[k] || k),
    stroke: { width: 0 },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: { show: false },
            },
            expandOnClick: true,
        },
    },
    legend: {
        position: 'bottom' as const,
        fontSize: '11px',
        labels: { colors: '#9ca3af' },
        markers: { size: 6, shape: 'circle' as const },
        itemMargin: { horizontal: 8, vertical: 4 },
    },
    dataLabels: { enabled: false },
    tooltip: {
        theme: 'dark',
        style: { fontSize: '12px' },
    },
}));

// Payment method doughnut
const methodLabels: Record<string, string> = {
    cash: 'Cash',
    card: 'Card',
    e_wallet: 'E-Wallet',
    bank_transfer: 'Bank Transfer',
    other: 'Other',
};
const methodColors: Record<string, string> = {
    cash: '#0d9488',
    card: '#3b82f6',
    e_wallet: '#f59e0b',
    bank_transfer: '#8b5cf6',
    other: '#6b7280',
};

const paymentEntries = computed(() => Object.entries(props.paymentsByMethod));
const paymentSeries = computed(() => paymentEntries.value.map(([, v]) => v));
const paymentOptions = computed(() => ({
    chart: {
        type: 'donut' as const,
        fontFamily: 'inherit',
        animations: { enabled: true, easing: 'easeinout', speed: 600 },
    },
    colors: paymentEntries.value.map(([k]) => methodColors[k] || '#9ca3af'),
    labels: paymentEntries.value.map(([k]) => methodLabels[k] || k),
    stroke: { width: 0 },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: { show: false },
            },
            expandOnClick: true,
        },
    },
    legend: {
        position: 'bottom' as const,
        fontSize: '11px',
        labels: { colors: '#9ca3af' },
        markers: { size: 6, shape: 'circle' as const },
        itemMargin: { horizontal: 8, vertical: 4 },
    },
    dataLabels: { enabled: false },
    tooltip: {
        theme: 'dark',
        style: { fontSize: '12px' },
    },
}));

const statusBadgeClass: Record<string, string> = {
    completed: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    voided: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    refunded: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
};

const topProductMax = computed(() => {
    if (props.topProducts.length === 0) return 1;
    return Math.max(...props.topProducts.map(p => p.revenue));
});
</script>

<template>
    <Head :title="$t('nav.dashboard')" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ $t('dashboard.welcomeBack', { name: firstName }) }}</h1>
                    <p class="text-sm text-muted-foreground">{{ $t('dashboard.todayOverview') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold"
                        :class="stats.subscription_status === 'active'
                            ? 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'"
                    >
                        {{ stats.plan_name }} {{ $t('dashboard.plan') }}
                    </span>
                </div>
            </div>

            <!-- Row 1: Key Metric Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Today Revenue -->
                <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">{{ $t('dashboard.todayRevenue') }}</p>
                        <div class="rounded-lg bg-teal-100 p-2 dark:bg-teal-900/30">
                            <DollarSign class="h-4 w-4 text-teal-600" />
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold">{{ formatCurrency(todayRevenue) }}</p>
                    <div class="mt-1 flex items-center gap-1 text-xs">
                        <span v-if="revenueChange >= 0" class="flex items-center text-teal-600">
                            <TrendingUp class="mr-0.5 h-3 w-3" /> +{{ revenueChange }}%
                        </span>
                        <span v-else class="flex items-center text-red-500">
                            <TrendingDown class="mr-0.5 h-3 w-3" /> {{ revenueChange }}%
                        </span>
                        <span class="text-muted-foreground">{{ $t('common.vsYesterday') }}</span>
                    </div>
                </div>

                <!-- Today Orders -->
                <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">{{ $t('dashboard.todayOrders') }}</p>
                        <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                            <ShoppingCart class="h-4 w-4 text-blue-600" />
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold">{{ todayOrderCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ $t('dashboard.completedOrders') }}</p>
                </div>

                <!-- Products -->
                <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">{{ $t('dashboard.products') }}</p>
                        <div class="rounded-lg bg-orange-100 p-2 dark:bg-orange-900/30">
                            <Package class="h-4 w-4 text-orange-600" />
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold">{{ stats.products_count }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ stats.max_products ? `${$t('common.of')} ${stats.max_products} ${$t('common.max')}` : $t('common.unlimited') }}</p>
                </div>

                <!-- Team -->
                <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">{{ $t('dashboard.teamMembers') }}</p>
                        <div class="rounded-lg bg-violet-100 p-2 dark:bg-violet-900/30">
                            <Users class="h-4 w-4 text-violet-600" />
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold">{{ stats.users_count }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ stats.max_users ? `${$t('common.of')} ${stats.max_users} ${$t('common.max')}` : $t('common.unlimited') }}</p>
                </div>
            </div>

            <!-- Row 2: Sales Chart + Order Status Pie -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Sales Trend Line Chart (span 2) -->
                <div class="rounded-xl border bg-white p-5 shadow-sm lg:col-span-2 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold">{{ $t('dashboard.salesTrend') }}</h3>
                            <p class="text-xs text-muted-foreground">{{ $t('dashboard.revenueLast7') }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-800">
                            <TrendingUp class="h-4 w-4 text-gray-500" />
                        </div>
                    </div>
                    <div v-if="salesTrend.length">
                        <apexchart type="area" height="256" :options="lineChartOptions" :series="lineSeries" />
                    </div>
                    <div v-else class="flex h-64 items-center justify-center text-sm text-muted-foreground">{{ $t('dashboard.noSalesData') }}</div>
                </div>

                <!-- Order Status Doughnut -->
                <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4">
                        <h3 class="font-semibold">{{ $t('dashboard.orderStatus') }}</h3>
                        <p class="text-xs text-muted-foreground">{{ $t('dashboard.last30Days') }}</p>
                    </div>
                    <div v-if="Object.keys(ordersByStatus).length">
                        <apexchart type="donut" height="224" :options="orderStatusOptions" :series="orderStatusSeries" />
                    </div>
                    <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">{{ $t('dashboard.noOrdersYet') }}</div>
                </div>
            </div>

            <!-- Row 3: Payment Methods + Top Products -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Payment Method Doughnut -->
                <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4">
                        <h3 class="font-semibold">{{ $t('dashboard.paymentMethods') }}</h3>
                        <p class="text-xs text-muted-foreground">{{ $t('dashboard.last30ByAmount') }}</p>
                    </div>
                    <div v-if="Object.keys(paymentsByMethod).length">
                        <apexchart type="donut" height="224" :options="paymentOptions" :series="paymentSeries" />
                    </div>
                    <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">{{ $t('dashboard.noPaymentsYet') }}</div>
                </div>

                <!-- Top Products -->
                <div class="rounded-xl border bg-white p-5 shadow-sm lg:col-span-2 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold">{{ $t('dashboard.topProducts') }}</h3>
                            <p class="text-xs text-muted-foreground">{{ $t('dashboard.bestSellers30') }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-800">
                            <BarChart3 class="h-4 w-4 text-gray-500" />
                        </div>
                    </div>
                    <div v-if="topProducts.length" class="space-y-4">
                        <div v-for="(product, i) in topProducts" :key="product.name" class="flex items-center gap-4">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ i + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="truncate text-sm font-medium">{{ product.name }}</p>
                                    <p class="shrink-0 text-sm font-semibold">{{ formatCurrency(product.revenue) }}</p>
                                </div>
                                <div class="mt-1.5 flex items-center gap-3">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                        <div
                                            class="h-full rounded-full bg-teal-500"
                                            :style="{ width: (product.revenue / topProductMax * 100) + '%' }"
                                        ></div>
                                    </div>
                                    <span class="shrink-0 text-xs text-muted-foreground">{{ product.qty }} {{ $t('dashboard.sold') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex h-40 items-center justify-center text-sm text-muted-foreground">{{ $t('dashboard.noProductData') }}</div>
                </div>
            </div>

            <!-- Row 4: Recent Orders + Quick Info Sidebar -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Recent Orders -->
                <div class="rounded-xl border bg-white p-5 shadow-sm lg:col-span-2 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold">{{ $t('dashboard.recentOrders') }}</h3>
                            <p class="text-xs text-muted-foreground">{{ $t('dashboard.latest10') }}</p>
                        </div>
                        <Link :href="tenantUrl('orders')" class="flex items-center gap-1 text-xs font-medium text-teal-600 hover:text-teal-700">
                            {{ $t('common.viewAll') }} <ArrowUpRight class="h-3 w-3" />
                        </Link>
                    </div>
                    <div v-if="recentOrders.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-xs text-muted-foreground dark:border-gray-800">
                                    <th class="pb-3 font-medium">{{ $t('dashboard.order') }}</th>
                                    <th class="pb-3 font-medium">{{ $t('dashboard.branch') }}</th>
                                    <th class="pb-3 font-medium">{{ $t('common.total') }}</th>
                                    <th class="pb-3 font-medium">{{ $t('common.status') }}</th>
                                    <th class="pb-3 text-right font-medium">{{ $t('common.time') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in recentOrders" :key="order.id" class="border-b last:border-0 dark:border-gray-800">
                                    <td class="py-3 font-medium">{{ order.order_number }}</td>
                                    <td class="py-3 text-muted-foreground">{{ order.branch }}</td>
                                    <td class="py-3 font-medium">{{ formatCurrency(order.total) }}</td>
                                    <td class="py-3">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadgeClass[order.status] ?? 'bg-gray-100 text-gray-700'">
                                            {{ statusLabels[order.status] || order.status }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right text-xs text-muted-foreground">{{ order.created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex h-40 items-center justify-center text-sm text-muted-foreground">{{ $t('dashboard.noOrdersYet') }}</div>
                </div>

                <!-- Quick Info + Actions -->
                <div class="flex flex-col gap-4">
                    <!-- Store Overview Mini Cards -->
                    <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-3 font-semibold">{{ $t('dashboard.storeOverview') }}</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                <Building2 class="mb-1 h-4 w-4 text-teal-600" />
                                <p class="text-lg font-bold">{{ stats.branches_count }}</p>
                                <p class="text-xs text-muted-foreground">{{ $t('nav.branches') }}</p>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                <Shield class="mb-1 h-4 w-4 text-indigo-600" />
                                <p class="text-lg font-bold">{{ stats.roles_count }}</p>
                                <p class="text-xs text-muted-foreground">{{ $t('nav.roles') }}</p>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                <FolderOpen class="mb-1 h-4 w-4 text-amber-600" />
                                <p class="text-lg font-bold">{{ stats.categories_count }}</p>
                                <p class="text-xs text-muted-foreground">{{ $t('nav.categories') }}</p>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                                <CreditCard class="mb-1 h-4 w-4 text-blue-600" />
                                <p class="text-lg font-bold capitalize">{{ stats.subscription_status }}</p>
                                <p class="text-xs text-muted-foreground">Status</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="mb-3 font-semibold">{{ $t('dashboard.quickActions') }}</h3>
                        <div class="flex flex-col gap-2">
                            <Link
                                :href="tenantUrl('branches/create')"
                                class="flex items-center gap-3 rounded-lg bg-gray-50 p-3 transition hover:bg-teal-50 dark:bg-gray-800/50 dark:hover:bg-teal-900/20"
                            >
                                <Plus class="h-4 w-4 text-teal-600" />
                                <span class="text-sm font-medium">{{ $t('dashboard.addBranch') }}</span>
                            </Link>
                            <Link
                                :href="tenantUrl('users')"
                                class="flex items-center gap-3 rounded-lg bg-gray-50 p-3 transition hover:bg-blue-50 dark:bg-gray-800/50 dark:hover:bg-blue-900/20"
                            >
                                <UserPlus class="h-4 w-4 text-blue-600" />
                                <span class="text-sm font-medium">{{ $t('dashboard.inviteUser') }}</span>
                            </Link>
                            <Link
                                :href="tenantUrl('products/create')"
                                class="flex items-center gap-3 rounded-lg bg-gray-50 p-3 transition hover:bg-orange-50 dark:bg-gray-800/50 dark:hover:bg-orange-900/20"
                            >
                                <Package class="h-4 w-4 text-orange-600" />
                                <span class="text-sm font-medium">{{ $t('dashboard.addProduct') }}</span>
                            </Link>
                            <a
                                :href="tenantUrl('pos')"
                                target="_blank"
                                class="flex items-center gap-3 rounded-lg bg-teal-50 p-3 transition hover:bg-teal-100 dark:bg-teal-900/20 dark:hover:bg-teal-900/30"
                            >
                                <ShoppingCart class="h-4 w-4 text-teal-600" />
                                <span class="text-sm font-medium text-teal-700 dark:text-teal-400">{{ $t('dashboard.openPos') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
