<script setup lang="ts">
import { computed } from 'vue';
import { GitBranch, MapPin } from 'lucide-vue-next';
import type { BranchComparisonItem } from '@/types';

const props = defineProps<{
    data: BranchComparisonItem[];
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

const sorted = computed(() => [...props.data].sort((a, b) => b.total_revenue - a.total_revenue));
const maxRevenue = computed(() => sorted.value.length ? sorted.value[0].total_revenue : 1);

const branchColors = [
    { bg: 'rgba(20, 184, 166, 0.2)', border: '#14b8a6' },
    { bg: 'rgba(99, 102, 241, 0.2)', border: '#6366f1' },
    { bg: 'rgba(245, 158, 11, 0.2)', border: '#f59e0b' },
    { bg: 'rgba(244, 63, 94, 0.2)', border: '#f43f5e' },
    { bg: 'rgba(139, 92, 246, 0.2)', border: '#8b5cf6' },
];

const series = computed(() => [
    {
        name: 'Revenue',
        data: props.data.map(i => i.total_revenue),
    },
    {
        name: 'Orders',
        data: props.data.map(i => i.order_count),
    },
]);

const chartOptions = computed(() => ({
    chart: {
        type: 'bar' as const,
        height: 300,
        toolbar: { show: false },
        fontFamily: 'inherit',
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 500,
        },
    },
    colors: ['#14b8a6', '#94a3b8'],
    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: '55%',
            dataLabels: { position: 'top' },
        },
    },
    dataLabels: { enabled: false },
    stroke: { show: true, width: 2, colors: ['transparent'] },
    xaxis: {
        categories: props.data.map(i => i.branch_name),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
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
    fill: { opacity: [0.85, 0.25] },
    legend: { show: false },
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
}));
</script>

<template>
    <div class="overflow-hidden rounded-2xl border bg-card">
        <div class="flex items-center justify-between border-b p-5">
            <div>
                <h3 class="text-lg font-semibold">Branch Comparison</h3>
                <p class="text-sm text-muted-foreground">Performance metrics across locations</p>
            </div>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded bg-teal-500" />
                    <span class="text-sm text-muted-foreground">Revenue</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded bg-slate-400/40" />
                    <span class="text-sm text-muted-foreground">Orders</span>
                </div>
            </div>
        </div>

        <div class="p-5">
            <div v-if="data.length">
                <apexchart type="bar" height="300" :options="chartOptions" :series="series" />
            </div>
            <div v-else class="flex h-[300px] flex-col items-center justify-center gap-2 text-muted-foreground">
                <GitBranch class="h-10 w-10 opacity-30" />
                <p>No branch data available.</p>
            </div>
        </div>

        <!-- Branch cards -->
        <div v-if="data.length" class="border-t">
            <div
                v-for="(item, idx) in sorted"
                :key="item.branch_id"
                class="flex items-center gap-4 border-b px-5 py-3.5 last:border-0 transition-colors hover:bg-muted/30"
            >
                <div
                    class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl"
                    :style="{ backgroundColor: branchColors[idx % branchColors.length].bg, borderColor: branchColors[idx % branchColors.length].border }"
                    style="border-width: 1.5px;"
                >
                    <MapPin class="h-4 w-4" :style="{ color: branchColors[idx % branchColors.length].border }" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium">{{ item.branch_name }}</p>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :style="{
                                width: `${(item.total_revenue / maxRevenue) * 100}%`,
                                backgroundColor: branchColors[idx % branchColors.length].border,
                            }"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-6 text-right">
                    <div>
                        <p class="text-xs text-muted-foreground">Revenue</p>
                        <p class="text-sm font-semibold">{{ formatCurrency(item.total_revenue) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Orders</p>
                        <p class="text-sm font-semibold">{{ item.order_count }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Avg</p>
                        <p class="text-sm font-semibold">{{ formatCurrency(item.avg_order_value) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
