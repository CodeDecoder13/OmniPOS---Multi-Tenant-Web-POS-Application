<script setup lang="ts">
import { computed } from 'vue';
import { TrendingUp, BarChart3 } from 'lucide-vue-next';
import type { SalesTrend } from '@/types';

const props = defineProps<{
    data: SalesTrend;
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

const totalRevenue = computed(() => props.data.revenue.reduce((a, b) => a + b, 0));
const totalOrders = computed(() => props.data.order_count.reduce((a, b) => a + b, 0));

const series = computed(() => [
    {
        name: 'Revenue',
        type: 'area',
        data: props.data.revenue,
    },
    {
        name: 'Orders',
        type: 'bar',
        data: props.data.order_count,
    },
]);

const chartOptions = computed(() => ({
    chart: {
        type: 'line' as const,
        height: 380,
        toolbar: {
            show: true,
            tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false },
        },
        fontFamily: 'inherit',
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 600,
            animateGradually: { enabled: true, delay: 100 },
            dynamicAnimation: { enabled: true, speed: 300 },
        },
    },
    colors: ['#14b8a6', '#818cf8'],
    stroke: {
        width: [3, 0],
        curve: 'smooth' as const,
    },
    fill: {
        type: ['gradient', 'solid'],
        gradient: {
            shade: 'light',
            type: 'vertical',
            shadeIntensity: 0.2,
            opacityFrom: 0.35,
            opacityTo: 0.05,
            stops: [0, 90, 100],
        },
        opacity: [1, 0.2],
    },
    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: '50%',
        },
    },
    xaxis: {
        categories: props.data.labels,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: '#94a3b8', fontSize: '11px' },
        },
    },
    yaxis: [
        {
            title: { text: '' },
            labels: {
                style: { colors: '#94a3b8', fontSize: '11px' },
                formatter: (val: number) => val >= 1000 ? `${(val / 1000).toFixed(0)}K` : `${val}`,
            },
        },
        {
            opposite: true,
            title: { text: '' },
            labels: {
                style: { colors: '#94a3b8', fontSize: '11px' },
                formatter: (val: number) => `${Math.round(val)}`,
            },
        },
    ],
    grid: {
        borderColor: 'rgba(148, 163, 184, 0.08)',
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
    },
    legend: { show: false },
    dataLabels: { enabled: false },
    tooltip: {
        shared: true,
        intersect: false,
        theme: 'dark',
        style: { fontSize: '12px' },
        y: {
            formatter: (val: number, opts: any) => {
                if (opts.seriesIndex === 0) return formatCurrency(val);
                return `${val} orders`;
            },
        },
    },
    markers: {
        size: [0, 0],
        hover: { size: 6, sizeOffset: 3 },
    },
}));
</script>

<template>
    <div class="overflow-hidden rounded-2xl border bg-card">
        <div class="flex items-center justify-between border-b p-5">
            <div>
                <h3 class="text-lg font-semibold">Sales Trend</h3>
                <p class="text-sm text-muted-foreground">Revenue and order volume over time</p>
            </div>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-teal-500" />
                    <span class="text-sm text-muted-foreground">Revenue</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded bg-indigo-400/40" />
                    <span class="text-sm text-muted-foreground">Orders</span>
                </div>
            </div>
        </div>

        <!-- Quick stats row -->
        <div class="grid grid-cols-2 gap-4 border-b px-5 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-500/10">
                    <TrendingUp class="h-4 w-4 text-teal-500" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Period Revenue</p>
                    <p class="text-sm font-semibold">{{ formatCurrency(totalRevenue) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/10">
                    <BarChart3 class="h-4 w-4 text-indigo-500" />
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Period Orders</p>
                    <p class="text-sm font-semibold">{{ totalOrders.toLocaleString() }}</p>
                </div>
            </div>
        </div>

        <div class="p-5">
            <div v-if="data.labels.length">
                <apexchart type="line" height="380" :options="chartOptions" :series="series" />
            </div>
            <div v-else class="flex h-[380px] flex-col items-center justify-center gap-2 text-muted-foreground">
                <BarChart3 class="h-10 w-10 opacity-30" />
                <p>No data for the selected period.</p>
            </div>
        </div>
    </div>
</template>
