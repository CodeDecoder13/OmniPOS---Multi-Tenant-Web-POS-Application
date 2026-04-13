<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    Package, Plus, ShoppingCart, UserPlus,
    TrendingUp, TrendingDown, DollarSign,
    ArrowUpRight, Sparkles, Crown,
} from 'lucide-vue-next';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import TenantLayout from '@/layouts/TenantLayout.vue';
import WelcomeBackModal from '@/components/WelcomeBackModal.vue';
import PinSetupModal from '@/components/PinSetupModal.vue';
import type { BreadcrumbItem } from '@/types';
import type { AIInsightsSummary, ReleaseNote } from '@/types/models';
import { useTenant } from '@/composables/useTenant';
import { useCurrency } from '@/composables/useCurrency';
import { useFlash } from '@/composables/useFlash';

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
    releaseNotes?: ReleaseNote[];
    needsPinSetup?: boolean;
    aiInsights: AIInsightsSummary | null;
}>();

const page = usePage();
const { tenantUrl } = useTenant();
const { formatCurrency, formatCurrencyShort } = useCurrency();
const { flash } = useFlash();

const user = page.props.auth.user as { name: string };
const firstName = user.name.split(' ')[0];
const tenantName = (page.props.tenant as { name: string } | null)?.name ?? '';

const showWelcomeModal = ref(!!flash.value.showWelcome);
const showPinModal = ref(!!props.needsPinSetup);

function closeWelcomeModal() {
    showWelcomeModal.value = false;
    if (props.releaseNotes?.length) {
        router.post(tenantUrl('release-notes/mark-seen'), {}, { preserveState: true, preserveScroll: true });
    }
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
]);

const revenueChange = computed(() => {
    if (props.yesterdayRevenue === 0) return props.todayRevenue > 0 ? 100 : 0;
    return Math.round(((props.todayRevenue - props.yesterdayRevenue) / props.yesterdayRevenue) * 100);
});

// Detect dark mode for chart theming
const isDark = computed(() => document.documentElement.classList.contains('dark'));

// Line chart - Sales Trend
const lineChartOptions = computed(() => ({
    chart: {
        type: 'area' as const,
        height: 256,
        toolbar: { show: false },
        fontFamily: 'inherit',
        sparkline: { enabled: false },
        background: 'transparent',
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 600,
            animateGradually: { enabled: true, delay: 100 },
        },
    },
    colors: ['#14b8a6'],
    stroke: { width: 3, curve: 'smooth' as const },
    fill: {
        type: 'gradient',
        gradient: {
            shade: isDark.value ? 'dark' : 'light',
            type: 'vertical',
            shadeIntensity: 0.1,
            opacityFrom: isDark.value ? 0.25 : 0.35,
            opacityTo: 0.02,
            stops: [0, 85, 100],
        },
    },
    xaxis: {
        categories: props.salesTrend.map(d => d.day),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: isDark.value ? '#64748b' : '#9ca3af', fontSize: '11px' } },
    },
    yaxis: {
        labels: {
            style: { colors: isDark.value ? '#64748b' : '#9ca3af', fontSize: '11px' },
            formatter: (val: number) => formatCurrencyShort(val),
        },
    },
    grid: {
        borderColor: isDark.value ? 'rgba(148, 163, 184, 0.06)' : 'rgba(156, 163, 175, 0.12)',
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
    },
    markers: {
        size: 4,
        colors: ['#14b8a6'],
        strokeColors: isDark.value ? '#0f172a' : '#fff',
        strokeWidth: 2,
        hover: { size: 6 },
    },
    dataLabels: { enabled: false },
    legend: { show: false },
    tooltip: {
        theme: isDark.value ? 'dark' : 'light',
        style: { fontSize: '12px' },
        y: {
            formatter: (val: number) => formatCurrency(val),
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
    completed: '#14b8a6',
    pending: '#fbbf24',
    voided: '#f87171',
    refunded: '#a78bfa',
};

const orderStatusEntries = computed(() => Object.entries(props.ordersByStatus));
const orderStatusSeries = computed(() => orderStatusEntries.value.map(([, v]) => v));
const orderStatusOptions = computed(() => ({
    chart: {
        type: 'donut' as const,
        fontFamily: 'inherit',
        background: 'transparent',
        animations: { enabled: true, easing: 'easeinout', speed: 600 },
    },
    colors: orderStatusEntries.value.map(([k]) => statusColors[k] || '#9ca3af'),
    labels: orderStatusEntries.value.map(([k]) => statusLabels[k] || k),
    stroke: { width: 2, colors: [isDark.value ? '#0f172a' : '#ffffff'] },
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
        labels: { colors: isDark.value ? '#64748b' : '#9ca3af' },
        markers: { size: 6, shape: 'circle' as const },
        itemMargin: { horizontal: 8, vertical: 4 },
    },
    dataLabels: { enabled: false },
    tooltip: {
        theme: isDark.value ? 'dark' : 'light',
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
    cash: '#14b8a6',
    card: '#38bdf8',
    e_wallet: '#fbbf24',
    bank_transfer: '#a78bfa',
    other: '#94a3b8',
};

const paymentEntries = computed(() => Object.entries(props.paymentsByMethod));
const paymentSeries = computed(() => paymentEntries.value.map(([, v]) => v));
const paymentOptions = computed(() => ({
    chart: {
        type: 'donut' as const,
        fontFamily: 'inherit',
        background: 'transparent',
        animations: { enabled: true, easing: 'easeinout', speed: 600 },
    },
    colors: paymentEntries.value.map(([k]) => methodColors[k] || '#9ca3af'),
    labels: paymentEntries.value.map(([k]) => methodLabels[k] || k),
    stroke: { width: 2, colors: [isDark.value ? '#0f172a' : '#ffffff'] },
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
        labels: { colors: isDark.value ? '#64748b' : '#9ca3af' },
        markers: { size: 6, shape: 'circle' as const },
        itemMargin: { horizontal: 8, vertical: 4 },
    },
    dataLabels: { enabled: false },
    tooltip: {
        theme: isDark.value ? 'dark' : 'light',
        style: { fontSize: '12px' },
    },
}));

const statusBadgeClass: Record<string, string> = {
    completed: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    voided: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    refunded: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
};

// Tab state for combined chart cards
const salesTab = ref('trend');
const ordersTab = ref('status');

// Top Products horizontal bar chart
const topProductsBarOptions = computed(() => ({
    chart: { type: 'bar' as const, height: 256, toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
    colors: ['#14b8a6'],
    fill: {
        type: 'gradient',
        gradient: {
            shade: isDark.value ? 'dark' : 'light',
            type: 'horizontal',
            shadeIntensity: 0.1,
            opacityFrom: 1,
            opacityTo: 0.85,
            stops: [0, 100],
            colorStops: [
                { offset: 0, color: '#14b8a6', opacity: 1 },
                { offset: 100, color: '#34d399', opacity: 0.9 },
            ],
        },
    },
    xaxis: {
        categories: props.topProducts.map(p => p.name),
        labels: {
            style: { colors: isDark.value ? '#64748b' : '#9ca3af', fontSize: '11px' },
            formatter: (val: number) => formatCurrencyShort(val),
        },
    },
    yaxis: {
        labels: { style: { colors: isDark.value ? '#64748b' : '#9ca3af', fontSize: '11px' } },
    },
    grid: { borderColor: isDark.value ? 'rgba(148,163,184,0.06)' : 'rgba(156,163,175,0.12)', strokeDashArray: 4 },
    dataLabels: { enabled: false },
    tooltip: {
        theme: isDark.value ? 'dark' : 'light',
        y: { formatter: (val: number) => formatCurrency(val) },
    },
}));
const topProductsBarSeries = computed(() => [{ name: 'Revenue', data: props.topProducts.map(p => p.revenue) }]);
</script>

<template>
    <Head title="Dashboard" />

    <PinSetupModal
        :show="showPinModal && !showWelcomeModal"
        @close="showPinModal = false"
    />

    <WelcomeBackModal
        :show="showWelcomeModal"
        :user-name="firstName"
        :tenant-name="tenantName"
        :stats="{
            todayRevenue: props.todayRevenue,
            todayOrderCount: props.todayOrderCount,
            productsCount: props.stats.products_count,
            usersCount: props.stats.users_count,
        }"
        :release-notes="props.releaseNotes"
        @close="closeWelcomeModal"
    />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Welcome back, {{ firstName }}!</h1>
                    <p class="text-sm text-muted-foreground">Here's what's happening with your business today.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold"
                        :class="stats.subscription_status === 'active'
                            ? 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'"
                    >
                        {{ stats.plan_name }} Plan
                    </span>
                </div>
            </div>

            <!-- Row 1: Key Metrics + Quick Actions -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Today Revenue -->
                <div class="glass-card rounded-xl p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">Today's Revenue</p>
                        <div class="rounded-lg bg-gradient-to-br from-teal-400/90 to-teal-600/90 p-2 shadow-md shadow-teal-500/20">
                            <DollarSign class="h-4 w-4 text-white" />
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
                        <span class="text-muted-foreground">vs yesterday</span>
                    </div>
                </div>

                <!-- Today Orders -->
                <div class="glass-card rounded-xl p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">Today's Orders</p>
                        <div class="rounded-lg bg-gradient-to-br from-sky-400/90 to-blue-600/90 p-2 shadow-md shadow-blue-500/20">
                            <ShoppingCart class="h-4 w-4 text-white" />
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold">{{ todayOrderCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Completed orders</p>
                </div>

                <!-- Quick Actions -->
                <div class="glass-card rounded-xl p-5 sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-medium text-muted-foreground">Quick Actions</p>
                        <div class="rounded-lg bg-gradient-to-br from-emerald-400/90 to-teal-600/90 p-2 shadow-md shadow-teal-500/20">
                            <Plus class="h-4 w-4 text-white" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <Link
                            :href="tenantUrl('branches/create')"
                            class="flex items-center gap-2 rounded-lg bg-white/50 dark:bg-white/[0.04] p-2.5 border border-white/20 dark:border-white/[0.06] transition hover:bg-teal-50/80 dark:hover:bg-teal-900/20 hover:border-teal-200/50 dark:hover:border-teal-800/30"
                        >
                            <Plus class="h-3.5 w-3.5 text-teal-500 shrink-0" />
                            <span class="text-xs font-medium">Add Branch</span>
                        </Link>
                        <Link
                            :href="tenantUrl('users')"
                            class="flex items-center gap-2 rounded-lg bg-white/50 dark:bg-white/[0.04] p-2.5 border border-white/20 dark:border-white/[0.06] transition hover:bg-sky-50/80 dark:hover:bg-sky-900/20 hover:border-sky-200/50 dark:hover:border-sky-800/30"
                        >
                            <UserPlus class="h-3.5 w-3.5 text-sky-500 shrink-0" />
                            <span class="text-xs font-medium">Add Employee</span>
                        </Link>
                        <Link
                            :href="tenantUrl('products/create')"
                            class="flex items-center gap-2 rounded-lg bg-white/50 dark:bg-white/[0.04] p-2.5 border border-white/20 dark:border-white/[0.06] transition hover:bg-amber-50/80 dark:hover:bg-amber-900/20 hover:border-amber-200/50 dark:hover:border-amber-800/30"
                        >
                            <Package class="h-3.5 w-3.5 text-amber-500 shrink-0" />
                            <span class="text-xs font-medium">Add Product</span>
                        </Link>
                        <a
                            :href="tenantUrl('pos')"
                            target="_blank"
                            class="flex items-center gap-2 rounded-lg bg-gradient-to-r from-teal-500/10 to-emerald-500/10 p-2.5 border border-teal-200/30 dark:border-teal-800/30 transition hover:from-teal-500/20 hover:to-emerald-500/20"
                        >
                            <ShoppingCart class="h-3.5 w-3.5 text-teal-500 shrink-0" />
                            <span class="text-xs font-medium text-teal-700 dark:text-teal-400">Open POS</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Row 2: AI Insights -->
            <div v-if="aiInsights" class="relative overflow-hidden glass-card rounded-xl p-5 border border-violet-200/30 dark:border-violet-500/10">
                <div class="absolute inset-0 bg-gradient-to-r from-violet-500/[0.03] via-purple-500/[0.05] to-fuchsia-500/[0.03] dark:from-violet-500/[0.06] dark:via-purple-500/[0.08] dark:to-fuchsia-500/[0.06]"></div>
                <div class="relative">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 p-2 shadow-md shadow-violet-500/20">
                                <Sparkles class="h-4 w-4 text-white" />
                            </div>
                            <h3 class="font-semibold">AI Insights</h3>
                            <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-medium text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">Beta</span>
                        </div>
                        <Link :href="tenantUrl('ai-insights')" class="flex items-center gap-1 text-xs font-medium text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300">
                            View all insights <ArrowUpRight class="h-3 w-3" />
                        </Link>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-lg bg-white/60 p-4 dark:bg-white/[0.05] border border-white/30 dark:border-white/[0.08] backdrop-blur-sm">
                            <p class="text-xs text-muted-foreground mb-1">Top Insight</p>
                            <p class="text-sm font-medium leading-snug">{{ aiInsights.top_insight }}</p>
                        </div>
                        <div class="rounded-lg bg-white/60 p-4 dark:bg-white/[0.05] border border-white/30 dark:border-white/[0.08] backdrop-blur-sm">
                            <p class="text-xs text-muted-foreground mb-1">Pattern</p>
                            <p class="text-sm font-medium leading-snug">{{ aiInsights.secondary_insight }}</p>
                        </div>
                        <div class="rounded-lg bg-white/60 p-4 dark:bg-white/[0.05] border border-white/30 dark:border-white/[0.08] backdrop-blur-sm">
                            <p class="text-xs text-muted-foreground mb-1">7-Day Forecast</p>
                            <p class="text-lg font-bold text-violet-600 dark:text-violet-400">{{ formatCurrency(aiInsights.projected_revenue_7d) }}</p>
                            <p class="text-xs text-muted-foreground">projected revenue</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: AI Insights Upgrade Prompt -->
            <div v-else class="relative overflow-hidden glass-card rounded-xl p-5 border border-amber-200/30 dark:border-amber-500/10">
                <div class="absolute inset-0 bg-gradient-to-r from-amber-500/[0.03] via-yellow-500/[0.05] to-orange-500/[0.03] dark:from-amber-500/[0.06] dark:via-yellow-500/[0.08] dark:to-orange-500/[0.06]"></div>
                <div class="relative flex flex-col items-center justify-center py-6 text-center">
                    <div class="rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 p-3 shadow-md shadow-amber-500/20 mb-4">
                        <Crown class="h-6 w-6 text-white" />
                    </div>
                    <h3 class="text-lg font-semibold mb-1">Unlock AI Insights</h3>
                    <p class="text-sm text-muted-foreground mb-4 max-w-md">
                        Get AI-powered business insights, revenue forecasts, and smart recommendations. Available on Pro and Enterprise plans.
                    </p>
                    <Link
                        :href="tenantUrl('settings')"
                        class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 px-4 py-2 text-sm font-medium text-white shadow-md shadow-amber-500/20 transition hover:from-amber-600 hover:to-amber-700"
                    >
                        <Crown class="h-4 w-4" />
                        Upgrade Plan
                    </Link>
                </div>
            </div>

            <!-- Row 3: Charts -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Sales Trend / Top Products (tabbed) -->
                <div class="glass-card rounded-xl p-5 lg:col-span-2">
                    <Tabs v-model="salesTab" default-value="trend">
                        <div class="mb-4 flex items-center justify-between">
                            <TabsList class="h-8 bg-white/30 dark:bg-white/[0.04]">
                                <TabsTrigger value="trend" class="text-xs px-3 py-1">Sales Trend</TabsTrigger>
                                <TabsTrigger value="products" class="text-xs px-3 py-1">Top Products</TabsTrigger>
                            </TabsList>
                            <div class="rounded-lg bg-gradient-to-br from-teal-400/20 to-teal-600/20 p-2 dark:from-teal-400/10 dark:to-teal-600/10">
                                <TrendingUp class="h-4 w-4 text-teal-500" />
                            </div>
                        </div>
                        <TabsContent value="trend" class="mt-0">
                            <div v-if="salesTrend.length">
                                <apexchart type="area" height="256" :options="lineChartOptions" :series="lineSeries" />
                            </div>
                            <div v-else class="flex h-64 items-center justify-center text-sm text-muted-foreground">No sales data yet</div>
                        </TabsContent>
                        <TabsContent value="products" class="mt-0">
                            <div v-if="topProducts.length">
                                <apexchart type="bar" height="256" :options="topProductsBarOptions" :series="topProductsBarSeries" />
                            </div>
                            <div v-else class="flex h-64 items-center justify-center text-sm text-muted-foreground">No product data yet</div>
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- Order Status / Payment Methods (tabbed) -->
                <div class="glass-card rounded-xl p-5">
                    <Tabs v-model="ordersTab" default-value="status">
                        <div class="mb-4">
                            <TabsList class="h-8 bg-white/30 dark:bg-white/[0.04]">
                                <TabsTrigger value="status" class="text-xs px-3 py-1">Orders</TabsTrigger>
                                <TabsTrigger value="payments" class="text-xs px-3 py-1">Payments</TabsTrigger>
                            </TabsList>
                        </div>
                        <TabsContent value="status" class="mt-0">
                            <div v-if="Object.keys(ordersByStatus).length">
                                <apexchart type="donut" height="224" :options="orderStatusOptions" :series="orderStatusSeries" />
                            </div>
                            <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">No orders yet</div>
                        </TabsContent>
                        <TabsContent value="payments" class="mt-0">
                            <div v-if="Object.keys(paymentsByMethod).length">
                                <apexchart type="donut" height="224" :options="paymentOptions" :series="paymentSeries" />
                            </div>
                            <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">No payments yet</div>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>

            <!-- Row 4: Recent Orders -->
            <div>
                <div class="glass-card rounded-xl p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold">Recent Orders</h3>
                            <p class="text-xs text-muted-foreground">Latest 10 orders across all branches</p>
                        </div>
                        <Link :href="tenantUrl('orders')" class="flex items-center gap-1 text-xs font-medium text-teal-600 hover:text-teal-700">
                            View all <ArrowUpRight class="h-3 w-3" />
                        </Link>
                    </div>
                    <div v-if="recentOrders.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-xs text-muted-foreground border-white/20 dark:border-white/[0.06]">
                                    <th class="pb-3 font-medium">Order</th>
                                    <th class="pb-3 font-medium">Branch</th>
                                    <th class="pb-3 font-medium">Total</th>
                                    <th class="pb-3 font-medium">Status</th>
                                    <th class="pb-3 text-right font-medium">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in recentOrders" :key="order.id" class="border-b last:border-0 border-white/20 dark:border-white/[0.06] transition hover:bg-white/40 dark:hover:bg-white/[0.03]">
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
                    <div v-else class="flex h-40 items-center justify-center text-sm text-muted-foreground">No orders yet</div>
                </div>

            </div>
        </div>
    </TenantLayout>
</template>
