<script setup lang="ts">
import { ref, computed } from 'vue';
import { Package } from 'lucide-vue-next';
import type { TopProducts } from '@/types';

const props = defineProps<{
    data: TopProducts;
}>();

const mode = ref<'quantity' | 'revenue'>('quantity');

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

const items = computed(() => mode.value === 'quantity' ? props.data.by_quantity : props.data.by_revenue);

const maxValue = computed(() => {
    if (!items.value.length) return 1;
    return Math.max(...items.value.map(i => mode.value === 'quantity' ? i.total_quantity : i.total_revenue));
});

const series = computed(() => [{
    name: mode.value === 'quantity' ? 'Quantity Sold' : 'Revenue',
    data: items.value.map(i => mode.value === 'quantity' ? i.total_quantity : i.total_revenue),
}]);

const chartOptions = computed(() => ({
    chart: {
        type: 'bar' as const,
        height: 320,
        toolbar: { show: false },
        fontFamily: 'inherit',
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 500,
            animateGradually: { enabled: true, delay: 50 },
        },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 6,
            barHeight: '65%',
            distributed: true,
            dataLabels: { position: 'top' },
        },
    },
    colors: items.value.map((_, idx) => {
        const opacity = Math.max(0.2, 1 - idx * 0.08);
        return `rgba(20, 184, 166, ${opacity})`;
    }),
    dataLabels: { enabled: false },
    xaxis: {
        categories: items.value.map(i => i.product_name),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: '#94a3b8', fontSize: '11px' },
            formatter: (val: number) => {
                if (mode.value === 'revenue' && val >= 1000) return `${(val / 1000).toFixed(0)}K`;
                return `${val}`;
            },
        },
    },
    yaxis: {
        labels: {
            style: { colors: '#94a3b8', fontSize: '11px' },
            maxWidth: 140,
        },
    },
    grid: {
        borderColor: 'rgba(148, 163, 184, 0.08)',
        strokeDashArray: 4,
        yaxis: { lines: { show: false } },
    },
    legend: { show: false },
    tooltip: {
        theme: 'dark',
        style: { fontSize: '12px' },
        y: {
            formatter: (val: number) => mode.value === 'revenue' ? formatCurrency(val) : `${val} sold`,
        },
    },
}));
</script>

<template>
    <div class="overflow-hidden rounded-2xl border bg-card">
        <div class="flex items-center justify-between border-b p-5">
            <div>
                <h3 class="text-lg font-semibold">Top Products</h3>
                <p class="text-sm text-muted-foreground">Best performing products</p>
            </div>
            <div class="flex gap-1 rounded-lg bg-muted/60 p-1">
                <button
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-all"
                    :class="mode === 'quantity' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    @click="mode = 'quantity'"
                >
                    By Quantity
                </button>
                <button
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-all"
                    :class="mode === 'revenue' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    @click="mode = 'revenue'"
                >
                    By Revenue
                </button>
            </div>
        </div>

        <div class="p-5">
            <div v-if="items.length">
                <apexchart type="bar" height="320" :options="chartOptions" :series="series" />
            </div>
            <div v-else class="flex h-[320px] flex-col items-center justify-center gap-2 text-muted-foreground">
                <Package class="h-10 w-10 opacity-30" />
                <p>No product data available.</p>
            </div>
        </div>

        <!-- Ranked list -->
        <div v-if="items.length" class="border-t">
            <div
                v-for="(item, idx) in items"
                :key="item.product_name"
                class="flex items-center gap-4 border-b px-5 py-3 last:border-0 transition-colors hover:bg-muted/30"
            >
                <div
                    class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold"
                    :class="idx < 3
                        ? 'bg-gradient-to-br from-teal-500 to-emerald-600 text-white'
                        : 'bg-muted text-muted-foreground'"
                >
                    {{ idx + 1 }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">{{ item.product_name }}</p>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 transition-all duration-500"
                            :style="{ width: `${((mode === 'quantity' ? item.total_quantity : item.total_revenue) / maxValue) * 100}%` }"
                        />
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold">
                        {{ mode === 'quantity' ? item.total_quantity.toLocaleString() : formatCurrency(item.total_revenue) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ mode === 'quantity' ? formatCurrency(item.total_revenue) : `${item.total_quantity} sold` }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
