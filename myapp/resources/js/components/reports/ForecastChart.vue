<script setup lang="ts">
import { computed } from 'vue';
import { TrendingUp, TrendingDown, Calendar, Target } from 'lucide-vue-next';
import type { ForecastData } from '@/types';

const props = defineProps<{
    data: ForecastData;
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

// Revenue forecast line chart with historical + projected
const forecastSeries = computed(() => {
    const histLen = props.data.historical.revenue.length;
    const projLen = props.data.projected.revenue.length;
    const totalLen = histLen + projLen;

    // Historical: actual values then nulls
    const historicalData = [...props.data.historical.revenue, ...Array(projLen).fill(null)];
    // Projected: nulls then projected (overlap last historical point for continuity)
    const projectedData = [...Array(Math.max(0, histLen - 1)).fill(null), histLen > 0 ? props.data.historical.revenue[histLen - 1] : null, ...props.data.projected.revenue];
    // 7-day MA: padded
    const ma7 = [...props.data.moving_avg_7, ...Array(projLen).fill(null)];
    // 30-day MA: padded
    const ma30 = [...props.data.moving_avg_30, ...Array(projLen).fill(null)];

    return [
        { name: 'Actual Revenue', type: 'area', data: historicalData },
        { name: 'Projected', type: 'line', data: projectedData.slice(0, totalLen) },
        { name: '7-day MA', type: 'line', data: ma7.slice(0, totalLen) },
        { name: '30-day MA', type: 'line', data: ma30.slice(0, totalLen) },
    ];
});

const forecastLabels = computed(() => [
    ...props.data.historical.labels,
    ...props.data.projected.labels,
]);

const forecastOptions = computed(() => ({
    chart: { type: 'line' as const, height: 400, toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } }, fontFamily: 'inherit', animations: { enabled: true, easing: 'easeinout', speed: 600 } },
    colors: ['#14b8a6', '#f59e0b', '#818cf8', '#f43f5e'],
    stroke: {
        width: [3, 2, 2, 2],
        curve: 'smooth' as const,
        dashArray: [0, 6, 0, 0],
    },
    fill: {
        type: ['gradient', 'solid', 'solid', 'solid'],
        gradient: { shade: 'light', type: 'vertical', shadeIntensity: 0.2, opacityFrom: 0.25, opacityTo: 0.02, stops: [0, 90, 100] },
        opacity: [1, 1, 1, 1],
    },
    xaxis: {
        categories: forecastLabels.value,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: '#94a3b8', fontSize: '10px' },
            rotate: -45,
            rotateAlways: forecastLabels.value.length > 20,
            hideOverlappingLabels: true,
        },
    },
    yaxis: {
        labels: {
            style: { colors: '#94a3b8', fontSize: '11px' },
            formatter: (val: number) => val >= 1000 ? `${(val / 1000).toFixed(0)}K` : `${Math.round(val)}`,
        },
    },
    grid: { borderColor: 'rgba(148,163,184,0.08)', strokeDashArray: 4, xaxis: { lines: { show: false } } },
    legend: {
        position: 'top' as const,
        horizontalAlign: 'right' as const,
        fontSize: '12px',
        labels: { colors: '#94a3b8' },
        markers: { size: 4 },
    },
    dataLabels: { enabled: false },
    tooltip: {
        shared: true,
        intersect: false,
        theme: 'dark',
        style: { fontSize: '12px' },
        y: { formatter: (val: number | null) => val != null ? formatCurrency(val) : '-' },
    },
    markers: { size: [0, 0, 0, 0], hover: { size: 5, sizeOffset: 2 } },
    annotations: {
        xaxis: props.data.historical.labels.length > 0 ? [{
            x: props.data.historical.labels[props.data.historical.labels.length - 1],
            borderColor: '#64748b',
            strokeDashArray: 4,
            label: { text: 'Forecast Start', style: { color: '#fff', background: '#64748b', fontSize: '10px', padding: { left: 6, right: 6, top: 2, bottom: 2 } } },
        }] : [],
    },
}));

// Day of week bar chart
const dowSeries = computed(() => [{
    name: 'Avg Revenue',
    data: props.data.day_of_week_pattern.map(i => i.avg_revenue),
}]);

const dowOptions = computed(() => ({
    chart: { type: 'bar' as const, height: 260, toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#6366f1'],
    plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
    xaxis: {
        categories: props.data.day_of_week_pattern.map(i => i.day),
        labels: { style: { colors: '#94a3b8', fontSize: '12px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: {
            style: { colors: '#94a3b8', fontSize: '11px' },
            formatter: (val: number) => val >= 1000 ? `${(val / 1000).toFixed(1)}K` : `${Math.round(val)}`,
        },
    },
    grid: { borderColor: 'rgba(148,163,184,0.08)', strokeDashArray: 4, xaxis: { lines: { show: false } } },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark', style: { fontSize: '12px' }, y: { formatter: (val: number) => formatCurrency(val) } },
}));

const isPositiveGrowth = computed(() => props.data.growth_rate >= 0);
</script>

<template>
    <div class="space-y-6">
        <!-- Projection Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r opacity-80" :class="isPositiveGrowth ? 'from-emerald-500 to-teal-600' : 'from-red-500 to-rose-600'" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Growth Rate</p>
                        <p class="text-2xl font-bold tracking-tight" :class="isPositiveGrowth ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                            {{ isPositiveGrowth ? '+' : '' }}{{ data.growth_rate }}%
                        </p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl" :class="isPositiveGrowth ? 'bg-emerald-500/10' : 'bg-red-500/10'">
                        <component :is="isPositiveGrowth ? TrendingUp : TrendingDown" class="h-5 w-5" :class="isPositiveGrowth ? 'text-emerald-500' : 'text-red-500'" />
                    </div>
                </div>
            </div>
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 to-violet-600 opacity-80" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Next 7 Days</p>
                        <p class="text-2xl font-bold tracking-tight">{{ formatCurrency(data.projected_revenue_7d) }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/10">
                        <Target class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                </div>
            </div>
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-orange-600 opacity-80" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Next 14 Days</p>
                        <p class="text-2xl font-bold tracking-tight">{{ formatCurrency(data.projected_revenue_14d) }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10">
                        <Calendar class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </div>
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-rose-500 to-pink-600 opacity-80" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Next 30 Days</p>
                        <p class="text-2xl font-bold tracking-tight">{{ formatCurrency(data.projected_revenue_30d) }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-500/10">
                        <Calendar class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Forecast Chart -->
        <div class="overflow-hidden rounded-2xl border bg-card">
            <div class="border-b p-5">
                <h3 class="text-lg font-semibold">Revenue Forecast</h3>
                <p class="text-sm text-muted-foreground">Historical data with projected trend and moving averages</p>
            </div>
            <div class="p-5">
                <div v-if="data.historical.labels.length">
                    <apexchart type="line" height="400" :options="forecastOptions" :series="forecastSeries" />
                </div>
                <div v-else class="flex h-[400px] flex-col items-center justify-center gap-2 text-muted-foreground">
                    <TrendingUp class="h-10 w-10 opacity-30" />
                    <p>Not enough data for forecasting.</p>
                </div>
            </div>
        </div>

        <!-- Day of Week Pattern -->
        <div class="overflow-hidden rounded-2xl border bg-card">
            <div class="border-b p-5">
                <h3 class="text-lg font-semibold">Day-of-Week Revenue Pattern</h3>
                <p class="text-sm text-muted-foreground">Average daily revenue by day of week</p>
            </div>
            <div class="p-5">
                <div v-if="data.day_of_week_pattern.some(d => d.avg_revenue > 0)">
                    <apexchart type="bar" height="260" :options="dowOptions" :series="dowSeries" />
                </div>
                <div v-else class="flex h-[260px] flex-col items-center justify-center gap-2 text-muted-foreground">
                    <Calendar class="h-10 w-10 opacity-30" />
                    <p>No daily pattern data available.</p>
                </div>
            </div>
        </div>
    </div>
</template>
