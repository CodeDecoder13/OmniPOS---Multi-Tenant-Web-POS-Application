<script setup lang="ts">
import { computed } from 'vue';
import { Receipt, Percent, ShoppingBag, Info } from 'lucide-vue-next';
import type { TaxReport } from '@/types';

const props = defineProps<{
    data: TaxReport;
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

// Area chart — tax collection over time
const trendSeries = computed(() => [{
    name: 'Tax Collected',
    data: props.data.by_period.map(i => i.tax_amount),
}]);

const trendOptions = computed(() => ({
    chart: { type: 'area' as const, height: 340, toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } }, fontFamily: 'inherit', animations: { enabled: true, easing: 'easeinout', speed: 600 } },
    colors: ['#14b8a6'],
    stroke: { width: 3, curve: 'smooth' as const },
    fill: {
        type: 'gradient',
        gradient: { shade: 'light', type: 'vertical', shadeIntensity: 0.2, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] },
    },
    xaxis: {
        categories: props.data.by_period.map(i => i.period),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
    },
    yaxis: {
        labels: {
            style: { colors: '#94a3b8', fontSize: '11px' },
            formatter: (val: number) => val >= 1000 ? `${(val / 1000).toFixed(0)}K` : `${val}`,
        },
    },
    grid: { borderColor: 'rgba(148,163,184,0.08)', strokeDashArray: 4, xaxis: { lines: { show: false } } },
    legend: { show: false },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark', style: { fontSize: '12px' }, y: { formatter: (val: number) => formatCurrency(val) } },
    markers: { size: 0, hover: { size: 6, sizeOffset: 3 } },
}));

// Donut chart — tax by order type
const donutSeries = computed(() => props.data.by_order_type.map(i => i.tax_amount));
const donutColors = ['#6366f1', '#f59e0b', '#f43f5e', '#22c55e'];

const donutOptions = computed(() => ({
    chart: { type: 'donut' as const, fontFamily: 'inherit', animations: { enabled: true, easing: 'easeinout', speed: 600 } },
    colors: donutColors.slice(0, props.data.by_order_type.length),
    labels: props.data.by_order_type.map(i => i.label),
    stroke: { width: 2, colors: ['var(--color-card, #ffffff)'] },
    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,
                    name: { show: true, fontSize: '12px', color: '#94a3b8', offsetY: -8 },
                    value: { show: true, fontSize: '18px', fontWeight: '700', offsetY: 4, formatter: (val: string) => formatCurrency(Number(val)) },
                    total: { show: true, label: 'Total', fontSize: '12px', color: '#94a3b8', formatter: (w: any) => formatCurrency(w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0)) },
                },
            },
        },
    },
    legend: { show: false },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark', style: { fontSize: '12px' }, y: { formatter: (val: number) => formatCurrency(val) } },
}));
</script>

<template>
    <div class="space-y-6">
        <!-- Tax Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-teal-500 to-emerald-600 opacity-80" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Tax Collected</p>
                        <p class="text-2xl font-bold tracking-tight">{{ formatCurrency(data.total_tax) }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500/10">
                        <Receipt class="h-5 w-5 text-teal-600 dark:text-teal-400" />
                    </div>
                </div>
            </div>
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 to-violet-600 opacity-80" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Taxable Sales</p>
                        <p class="text-2xl font-bold tracking-tight">{{ formatCurrency(data.total_taxable) }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/10">
                        <ShoppingBag class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                </div>
            </div>
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-orange-600 opacity-80" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Effective Rate</p>
                        <p class="text-2xl font-bold tracking-tight">{{ data.effective_rate }}%</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10">
                        <Percent class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </div>
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-500 to-blue-600 opacity-80" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Tax Type</p>
                        <p class="text-2xl font-bold tracking-tight">{{ data.tax_inclusive ? 'Inclusive' : 'Exclusive' }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-500/10">
                        <Info class="h-5 w-5 text-cyan-600 dark:text-cyan-400" />
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Tax Trend -->
            <div class="overflow-hidden rounded-2xl border bg-card lg:col-span-2">
                <div class="border-b p-5">
                    <h3 class="text-lg font-semibold">Tax Collection Trend</h3>
                    <p class="text-sm text-muted-foreground">Tax amount collected over time</p>
                </div>
                <div class="p-5">
                    <div v-if="data.by_period.length">
                        <apexchart type="area" height="340" :options="trendOptions" :series="trendSeries" />
                    </div>
                    <div v-else class="flex h-[340px] flex-col items-center justify-center gap-2 text-muted-foreground">
                        <Receipt class="h-10 w-10 opacity-30" />
                        <p>No tax data for the selected period.</p>
                    </div>
                </div>
            </div>

            <!-- Tax by Order Type -->
            <div class="overflow-hidden rounded-2xl border bg-card">
                <div class="border-b p-5">
                    <h3 class="text-lg font-semibold">Tax by Order Type</h3>
                    <p class="text-sm text-muted-foreground">Distribution across order types</p>
                </div>
                <div class="flex items-center justify-center p-5">
                    <div v-if="data.by_order_type.length" class="w-[220px]">
                        <apexchart type="donut" height="220" :options="donutOptions" :series="donutSeries" />
                        <div class="mt-4 space-y-2">
                            <div v-for="(item, idx) in data.by_order_type" :key="item.type" class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: donutColors[idx] }" />
                                    <span class="text-muted-foreground">{{ item.label }}</span>
                                </div>
                                <span class="font-medium">{{ formatCurrency(item.tax_amount) }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex h-[220px] w-[220px] flex-col items-center justify-center gap-2 text-muted-foreground">
                        <Receipt class="h-10 w-10 opacity-30" />
                        <p>No data.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax Period Table -->
        <div v-if="data.by_period.length" class="overflow-hidden rounded-2xl border bg-card">
            <div class="border-b p-5">
                <h3 class="text-lg font-semibold">Period Breakdown</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b bg-muted/30 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                            <th class="px-5 py-3">Period</th>
                            <th class="px-5 py-3 text-right">Orders</th>
                            <th class="px-5 py-3 text-right">Taxable Amount</th>
                            <th class="px-5 py-3 text-right">Tax Collected</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in data.by_period" :key="item.period" class="transition-colors hover:bg-muted/20">
                            <td class="px-5 py-3 font-medium">{{ item.period }}</td>
                            <td class="px-5 py-3 text-right text-muted-foreground">{{ item.order_count.toLocaleString() }}</td>
                            <td class="px-5 py-3 text-right">{{ formatCurrency(item.taxable_amount) }}</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ formatCurrency(item.tax_amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
