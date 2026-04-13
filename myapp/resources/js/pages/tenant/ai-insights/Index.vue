<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    Sparkles, TrendingUp, TrendingDown, ShoppingCart, DollarSign,
    Target, Clock, Minus,
} from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { useTenant } from '@/composables/useTenant';
import { useCurrency } from '@/composables/useCurrency';
import type { AIInsightsSummary, AIInsight, ForecastData, PeakHour, ProductTrend } from '@/types/models';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    summary: AIInsightsSummary;
    forecast: ForecastData;
    peakHours: PeakHour[];
    productTrends: ProductTrend[];
    insights: AIInsight[];
}>();

const { tenantUrl } = useTenant();
const { formatCurrency, formatCurrencyShort } = useCurrency();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'AI Insights', href: tenantUrl('ai-insights') },
]);

const isDark = computed(() => document.documentElement.classList.contains('dark'));

// KPI cards config
const kpiCards = computed(() => [
    {
        label: 'Revenue Change',
        value: `${props.summary.revenue_change >= 0 ? '+' : ''}${props.summary.revenue_change}%`,
        positive: props.summary.revenue_change >= 0,
        icon: DollarSign,
        gradient: 'from-teal-400/90 to-teal-600/90',
        shadow: 'shadow-teal-500/20',
    },
    {
        label: 'Order Change',
        value: `${props.summary.order_change >= 0 ? '+' : ''}${props.summary.order_change}%`,
        positive: props.summary.order_change >= 0,
        icon: ShoppingCart,
        gradient: 'from-sky-400/90 to-blue-600/90',
        shadow: 'shadow-blue-500/20',
    },
    {
        label: '7-Day Forecast',
        value: formatCurrency(props.summary.projected_revenue_7d),
        positive: true,
        icon: Target,
        gradient: 'from-violet-400/90 to-purple-600/90',
        shadow: 'shadow-violet-500/20',
    },
    {
        label: 'Avg Order Change',
        value: `${props.summary.avg_order_change >= 0 ? '+' : ''}${props.summary.avg_order_change}%`,
        positive: props.summary.avg_order_change >= 0,
        icon: TrendingUp,
        gradient: 'from-amber-400/90 to-orange-600/90',
        shadow: 'shadow-orange-500/20',
    },
]);

// Forecast chart
const forecastSeries = computed(() => {
    const histLen = props.forecast.historical.revenue.length;
    const projLen = props.forecast.projected.revenue.length;
    const totalLen = histLen + projLen;

    const historicalData = [...props.forecast.historical.revenue, ...Array(projLen).fill(null)];
    const projectedData = [...Array(Math.max(0, histLen - 1)).fill(null), histLen > 0 ? props.forecast.historical.revenue[histLen - 1] : null, ...props.forecast.projected.revenue];
    const ma7 = [...props.forecast.moving_avg_7, ...Array(projLen).fill(null)];
    const ma30 = [...props.forecast.moving_avg_30, ...Array(projLen).fill(null)];

    return [
        { name: 'Actual Revenue', type: 'area', data: historicalData.slice(0, totalLen) },
        { name: 'Projected', type: 'line', data: projectedData.slice(0, totalLen) },
        { name: '7-day MA', type: 'line', data: ma7.slice(0, totalLen) },
        { name: '30-day MA', type: 'line', data: ma30.slice(0, totalLen) },
    ];
});

const forecastLabels = computed(() => [
    ...props.forecast.historical.labels,
    ...props.forecast.projected.labels,
]);

const forecastOptions = computed(() => ({
    chart: {
        type: 'line' as const,
        height: 350,
        toolbar: { show: false },
        fontFamily: 'inherit',
        background: 'transparent',
        animations: { enabled: true, easing: 'easeinout', speed: 600 },
    },
    colors: ['#14b8a6', '#f59e0b', '#818cf8', '#f43f5e'],
    stroke: { width: [3, 2, 2, 2], curve: 'smooth' as const, dashArray: [0, 6, 0, 0] },
    fill: {
        type: ['gradient', 'solid', 'solid', 'solid'],
        gradient: {
            shade: isDark.value ? 'dark' : 'light',
            type: 'vertical',
            shadeIntensity: 0.2,
            opacityFrom: isDark.value ? 0.2 : 0.25,
            opacityTo: 0.02,
            stops: [0, 90, 100],
        },
        opacity: [1, 1, 1, 1],
    },
    xaxis: {
        categories: forecastLabels.value,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: isDark.value ? '#64748b' : '#9ca3af', fontSize: '10px' },
            rotate: -45,
            rotateAlways: forecastLabels.value.length > 20,
            hideOverlappingLabels: true,
        },
    },
    yaxis: {
        labels: {
            style: { colors: isDark.value ? '#64748b' : '#9ca3af', fontSize: '11px' },
            formatter: (val: number) => formatCurrencyShort(val),
        },
    },
    grid: { borderColor: isDark.value ? 'rgba(148,163,184,0.06)' : 'rgba(156,163,175,0.12)', strokeDashArray: 4, xaxis: { lines: { show: false } } },
    legend: {
        position: 'top' as const,
        horizontalAlign: 'right' as const,
        fontSize: '11px',
        labels: { colors: isDark.value ? '#64748b' : '#9ca3af' },
        markers: { size: 4 },
    },
    dataLabels: { enabled: false },
    tooltip: {
        shared: true,
        intersect: false,
        theme: isDark.value ? 'dark' : 'light',
        style: { fontSize: '12px' },
        y: { formatter: (val: number | null) => val != null ? formatCurrency(val) : '-' },
    },
    markers: { size: [0, 0, 0, 0], hover: { size: 5, sizeOffset: 2 } },
    annotations: {
        xaxis: props.forecast.historical.labels.length > 0 ? [{
            x: props.forecast.historical.labels[props.forecast.historical.labels.length - 1],
            borderColor: isDark.value ? '#475569' : '#64748b',
            strokeDashArray: 4,
            label: { text: 'Forecast Start', style: { color: '#fff', background: '#64748b', fontSize: '10px', padding: { left: 6, right: 6, top: 2, bottom: 2 } } },
        }] : [],
    },
}));

// Peak hours chart
const peakHoursSeries = computed(() => [{
    name: 'Avg Orders',
    data: props.peakHours.map(h => h.avg_orders),
}]);

const peakHoursOptions = computed(() => ({
    chart: { type: 'bar' as const, height: 350, toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    colors: ['#8b5cf6'],
    plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
    fill: {
        type: 'gradient',
        gradient: {
            shade: isDark.value ? 'dark' : 'light',
            type: 'vertical',
            shadeIntensity: 0.1,
            opacityFrom: 1,
            opacityTo: 0.85,
            stops: [0, 100],
            colorStops: [
                { offset: 0, color: '#8b5cf6', opacity: 1 },
                { offset: 100, color: '#a78bfa', opacity: 0.9 },
            ],
        },
    },
    xaxis: {
        categories: props.peakHours.map(h => h.label),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: isDark.value ? '#64748b' : '#9ca3af', fontSize: '10px' },
            rotate: -45,
            rotateAlways: true,
        },
    },
    yaxis: {
        labels: {
            style: { colors: isDark.value ? '#64748b' : '#9ca3af', fontSize: '11px' },
            formatter: (val: number) => val.toFixed(1),
        },
    },
    grid: { borderColor: isDark.value ? 'rgba(148,163,184,0.06)' : 'rgba(156,163,175,0.12)', strokeDashArray: 4, xaxis: { lines: { show: false } } },
    dataLabels: { enabled: false },
    tooltip: {
        theme: isDark.value ? 'dark' : 'light',
        style: { fontSize: '12px' },
        y: { formatter: (val: number) => `${val.toFixed(1)} orders/day` },
    },
}));

const insightBorderClass: Record<string, string> = {
    positive: 'border-l-emerald-500',
    negative: 'border-l-red-500',
    neutral: 'border-l-slate-400 dark:border-l-slate-500',
};

const insightIconClass: Record<string, string> = {
    positive: 'text-emerald-500',
    negative: 'text-red-500',
    neutral: 'text-slate-400',
};
</script>

<template>
    <Head title="AI Insights" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-md">
                        <Sparkles class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">AI Insights</h1>
                        <p class="text-sm text-muted-foreground">Powered by statistical analysis</p>
                    </div>
                </div>
            </div>

            <!-- Row 1: KPI Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="kpi in kpiCards" :key="kpi.label" class="glass-card rounded-xl p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-muted-foreground">{{ kpi.label }}</p>
                        <div class="rounded-lg bg-gradient-to-br p-2 shadow-md" :class="[kpi.gradient, kpi.shadow]">
                            <component :is="kpi.icon" class="h-4 w-4 text-white" />
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold" :class="kpi.label.includes('Forecast') ? '' : (kpi.positive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400')">
                        {{ kpi.value }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">vs last month</p>
                </div>
            </div>

            <!-- Row 2: Insight Cards Grid -->
            <div v-if="insights.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(insight, idx) in insights"
                    :key="idx"
                    class="glass-card rounded-xl border-l-4 p-5"
                    :class="insightBorderClass[insight.type]"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="mb-1 flex items-center gap-2">
                                <component
                                    :is="insight.type === 'positive' ? TrendingUp : insight.type === 'negative' ? TrendingDown : Minus"
                                    class="h-4 w-4"
                                    :class="insightIconClass[insight.type]"
                                />
                                <h4 class="text-sm font-semibold">{{ insight.title }}</h4>
                            </div>
                            <p class="text-sm text-muted-foreground leading-relaxed">{{ insight.description }}</p>
                        </div>
                        <span
                            v-if="insight.metric"
                            class="ml-3 shrink-0 rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="{
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': insight.type === 'positive',
                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': insight.type === 'negative',
                                'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400': insight.type === 'neutral',
                            }"
                        >
                            {{ insight.metric }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Row 3: Forecast Chart + Peak Hours -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Sales Forecast (2 cols) -->
                <div class="glass-card rounded-xl p-5 lg:col-span-2">
                    <div class="mb-4">
                        <h3 class="font-semibold">Sales Forecast</h3>
                        <p class="text-xs text-muted-foreground">90-day historical + 30-day projection</p>
                    </div>
                    <div v-if="forecast.historical.labels.length">
                        <apexchart type="line" height="350" :options="forecastOptions" :series="forecastSeries" />
                    </div>
                    <div v-else class="flex h-[350px] flex-col items-center justify-center gap-2 text-muted-foreground">
                        <TrendingUp class="h-10 w-10 opacity-30" />
                        <p class="text-sm">Not enough data for forecasting</p>
                    </div>
                </div>

                <!-- Peak Hours (1 col) -->
                <div class="glass-card rounded-xl p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <Clock class="h-4 w-4 text-violet-500" />
                        <div>
                            <h3 class="font-semibold">Peak Hours</h3>
                            <p class="text-xs text-muted-foreground">Last 30 days</p>
                        </div>
                    </div>
                    <div v-if="peakHours.length">
                        <apexchart type="bar" height="350" :options="peakHoursOptions" :series="peakHoursSeries" />
                    </div>
                    <div v-else class="flex h-[350px] flex-col items-center justify-center gap-2 text-muted-foreground">
                        <Clock class="h-10 w-10 opacity-30" />
                        <p class="text-sm">No peak hour data yet</p>
                    </div>
                </div>
            </div>

            <!-- Row 4: Product Trends Table -->
            <div class="glass-card rounded-xl p-5">
                <div class="mb-4">
                    <h3 class="font-semibold">Product Trends</h3>
                    <p class="text-xs text-muted-foreground">Top 10 products — this month vs last month</p>
                </div>
                <div v-if="productTrends.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs text-muted-foreground border-white/20 dark:border-white/[0.06]">
                                <th class="pb-3 font-medium">Product</th>
                                <th class="pb-3 font-medium text-right">This Month</th>
                                <th class="pb-3 font-medium text-right">Last Month</th>
                                <th class="pb-3 font-medium text-right">Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="product in productTrends"
                                :key="product.name"
                                class="border-b last:border-0 border-white/20 dark:border-white/[0.06] transition hover:bg-white/40 dark:hover:bg-white/[0.03]"
                            >
                                <td class="py-3 font-medium">{{ product.name }}</td>
                                <td class="py-3 text-right">{{ formatCurrency(product.current_revenue) }}</td>
                                <td class="py-3 text-right text-muted-foreground">{{ formatCurrency(product.previous_revenue) }}</td>
                                <td class="py-3 text-right">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="product.change_percent >= 0
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                    >
                                        <component :is="product.change_percent >= 0 ? TrendingUp : TrendingDown" class="h-3 w-3" />
                                        {{ product.change_percent >= 0 ? '+' : '' }}{{ product.change_percent }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="flex h-40 items-center justify-center text-sm text-muted-foreground">
                    No product trend data yet
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
