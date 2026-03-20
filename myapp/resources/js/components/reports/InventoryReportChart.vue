<script setup lang="ts">
import { computed } from 'vue';
import { Package, AlertTriangle, Warehouse, ArrowUpDown } from 'lucide-vue-next';
import type { InventoryReport } from '@/types';

const props = defineProps<{
    data: InventoryReport;
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

const movementColors = ['#14b8a6', '#6366f1', '#f59e0b', '#f43f5e', '#8b5cf6', '#0ea5e9', '#84cc16', '#ec4899'];

const movementSeries = computed(() => [{
    name: 'Quantity',
    data: props.data.stock_movement.map(i => i.total_quantity),
}]);

const movementOptions = computed(() => ({
    chart: { type: 'bar' as const, height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
    colors: movementColors,
    plotOptions: {
        bar: { borderRadius: 6, columnWidth: '60%', distributed: true },
    },
    xaxis: {
        categories: props.data.stock_movement.map(i => i.label),
        labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
    },
    grid: { borderColor: 'rgba(148,163,184,0.08)', strokeDashArray: 4 },
    legend: { show: false },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark', style: { fontSize: '12px' } },
}));

const stockBarSeries = computed(() => {
    const categoryMap = new Map<string, { good: number; low: number; critical: number }>();
    for (const item of props.data.stock_levels) {
        const cat = item.category_name;
        if (!categoryMap.has(cat)) categoryMap.set(cat, { good: 0, low: 0, critical: 0 });
        const entry = categoryMap.get(cat)!;
        if (item.quantity_on_hand <= 0) entry.critical++;
        else if (item.is_low_stock) entry.low++;
        else entry.good++;
    }
    const categories = Array.from(categoryMap.keys());
    return {
        categories,
        series: [
            { name: 'Healthy', data: categories.map(c => categoryMap.get(c)!.good) },
            { name: 'Low Stock', data: categories.map(c => categoryMap.get(c)!.low) },
            { name: 'Critical', data: categories.map(c => categoryMap.get(c)!.critical) },
        ],
    };
});

const stockBarOptions = computed(() => ({
    chart: { type: 'bar' as const, height: 300, stacked: true, toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#22c55e', '#f59e0b', '#ef4444'],
    plotOptions: {
        bar: { horizontal: true, borderRadius: 4, barHeight: '60%' },
    },
    xaxis: {
        labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
        axisBorder: { show: false },
    },
    yaxis: {
        labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
    },
    grid: { borderColor: 'rgba(148,163,184,0.08)', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
    legend: { position: 'top' as const, horizontalAlign: 'right' as const, fontSize: '12px', labels: { colors: '#94a3b8' } },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark', style: { fontSize: '12px' } },
}));
</script>

<template>
    <div class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-teal-500 to-emerald-600 opacity-80" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Total Stock Value</p>
                        <p class="text-2xl font-bold tracking-tight">{{ formatCurrency(data.total_stock_value) }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500/10">
                        <Warehouse class="h-5 w-5 text-teal-600 dark:text-teal-400" />
                    </div>
                </div>
            </div>
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 to-violet-600 opacity-80" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Total Products</p>
                        <p class="text-2xl font-bold tracking-tight">{{ data.stock_levels.length.toLocaleString() }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/10">
                        <Package class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                </div>
            </div>
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-orange-600 opacity-80" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Low Stock Items</p>
                        <p class="text-2xl font-bold tracking-tight">{{ data.low_stock_items.length }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10">
                        <AlertTriangle class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </div>
            <div class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all hover:shadow-lg hover:-translate-y-0.5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-500 to-blue-600 opacity-80" />
                <div class="flex items-start justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-muted-foreground">Movement Types</p>
                        <p class="text-2xl font-bold tracking-tight">{{ data.stock_movement.length }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-500/10">
                        <ArrowUpDown class="h-5 w-5 text-cyan-600 dark:text-cyan-400" />
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Stock by Category -->
            <div class="overflow-hidden rounded-2xl border bg-card">
                <div class="border-b p-5">
                    <h3 class="text-lg font-semibold">Stock Health by Category</h3>
                    <p class="text-sm text-muted-foreground">Product count by stock status per category</p>
                </div>
                <div class="p-5">
                    <div v-if="stockBarSeries.categories.length">
                        <apexchart type="bar" height="300" :options="stockBarOptions" :series="stockBarSeries.series" />
                    </div>
                    <div v-else class="flex h-[300px] flex-col items-center justify-center gap-2 text-muted-foreground">
                        <Package class="h-10 w-10 opacity-30" />
                        <p>No inventory data available.</p>
                    </div>
                </div>
            </div>

            <!-- Stock Movement -->
            <div class="overflow-hidden rounded-2xl border bg-card">
                <div class="border-b p-5">
                    <h3 class="text-lg font-semibold">Stock Movement</h3>
                    <p class="text-sm text-muted-foreground">Adjustment volume by type in period</p>
                </div>
                <div class="p-5">
                    <div v-if="data.stock_movement.length">
                        <apexchart type="bar" height="300" :options="movementOptions" :series="movementSeries" />
                    </div>
                    <div v-else class="flex h-[300px] flex-col items-center justify-center gap-2 text-muted-foreground">
                        <ArrowUpDown class="h-10 w-10 opacity-30" />
                        <p>No stock movements in this period.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Branch Valuations -->
        <div v-if="data.branch_valuations.length > 1" class="overflow-hidden rounded-2xl border bg-card">
            <div class="border-b p-5">
                <h3 class="text-lg font-semibold">Stock Valuation by Branch</h3>
            </div>
            <div class="divide-y">
                <div v-for="branch in data.branch_valuations" :key="branch.branch_name" class="flex items-center justify-between px-5 py-3">
                    <div>
                        <p class="font-medium">{{ branch.branch_name }}</p>
                        <p class="text-sm text-muted-foreground">{{ branch.item_count }} items tracked</p>
                    </div>
                    <p class="text-lg font-semibold">{{ formatCurrency(branch.total_value) }}</p>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts Table -->
        <div v-if="data.low_stock_items.length" class="overflow-hidden rounded-2xl border bg-card">
            <div class="border-b p-5">
                <h3 class="text-lg font-semibold">Low Stock Alerts</h3>
                <p class="text-sm text-muted-foreground">Products below their reorder threshold</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b bg-muted/30 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3">Category</th>
                            <th class="px-5 py-3">Branch</th>
                            <th class="px-5 py-3 text-right">Current Qty</th>
                            <th class="px-5 py-3 text-right">Threshold</th>
                            <th class="px-5 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in data.low_stock_items" :key="`${item.product_id}-${item.branch_name}`" class="transition-colors hover:bg-muted/20">
                            <td class="px-5 py-3 font-medium">{{ item.product_name }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ item.category_name }}</td>
                            <td class="px-5 py-3 text-muted-foreground">{{ item.branch_name }}</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ item.quantity_on_hand }}</td>
                            <td class="px-5 py-3 text-right text-muted-foreground">{{ item.low_stock_threshold }}</td>
                            <td class="px-5 py-3 text-center">
                                <span v-if="item.quantity_on_hand <= 0" class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                    Out of Stock
                                </span>
                                <span v-else class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                    Low Stock
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
