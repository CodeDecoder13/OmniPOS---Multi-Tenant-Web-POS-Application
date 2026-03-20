<script setup lang="ts">
import { computed } from 'vue';
import { CreditCard, Banknote, Wallet, Building2, MoreHorizontal } from 'lucide-vue-next';
import type { PaymentBreakdownItem } from '@/types';

const props = defineProps<{
    data: PaymentBreakdownItem[];
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

const totalAmount = computed(() => props.data.reduce((a, b) => a + b.total_amount, 0));

const colorMap: Record<string, { color: string; icon: any; iconClass: string }> = {
    cash: { color: '#14b8a6', icon: Banknote, iconClass: 'text-teal-500' },
    card: { color: '#6366f1', icon: CreditCard, iconClass: 'text-indigo-500' },
    e_wallet: { color: '#f59e0b', icon: Wallet, iconClass: 'text-amber-500' },
    bank_transfer: { color: '#f43f5e', icon: Building2, iconClass: 'text-rose-500' },
    other: { color: '#8b5cf6', icon: MoreHorizontal, iconClass: 'text-violet-500' },
};

const fallbackColors = ['#14b8a6', '#6366f1', '#f59e0b', '#f43f5e', '#8b5cf6'];

function getColor(method: string, index: number) {
    return colorMap[method]?.color ?? fallbackColors[index % fallbackColors.length];
}

function getInfo(method: string) {
    return colorMap[method] ?? colorMap['other'];
}

const series = computed(() => props.data.map(i => i.total_amount));

const chartOptions = computed(() => ({
    chart: {
        type: 'donut' as const,
        fontFamily: 'inherit',
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 600,
            animateGradually: { enabled: true, delay: 80 },
        },
    },
    colors: props.data.map((i, idx) => getColor(i.method, idx)),
    labels: props.data.map(i => i.label),
    stroke: { width: 2, colors: ['var(--color-card, #ffffff)'] },
    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: '12px',
                        color: '#94a3b8',
                        offsetY: -8,
                    },
                    value: {
                        show: true,
                        fontSize: '20px',
                        fontWeight: '700',
                        offsetY: 4,
                        formatter: (val: string) => formatCurrency(Number(val)),
                    },
                    total: {
                        show: true,
                        label: 'Total',
                        fontSize: '12px',
                        color: '#94a3b8',
                        formatter: (w: any) => formatCurrency(w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0)),
                    },
                },
            },
        },
    },
    legend: { show: false },
    dataLabels: { enabled: false },
    tooltip: {
        theme: 'dark',
        style: { fontSize: '12px' },
        y: {
            formatter: (val: number) => formatCurrency(val),
        },
    },
}));
</script>

<template>
    <div class="overflow-hidden rounded-2xl border bg-card">
        <div class="border-b p-5">
            <h3 class="text-lg font-semibold">Payment Breakdown</h3>
            <p class="text-sm text-muted-foreground">Revenue distribution by payment method</p>
        </div>

        <div class="p-5">
            <div class="grid gap-8 md:grid-cols-2">
                <!-- Chart with center label -->
                <div class="flex items-center justify-center">
                    <div v-if="data.length" class="w-[280px]">
                        <apexchart type="donut" height="280" :options="chartOptions" :series="series" />
                    </div>
                    <div v-else class="flex h-[280px] w-[280px] flex-col items-center justify-center gap-2 text-muted-foreground">
                        <CreditCard class="h-10 w-10 opacity-30" />
                        <p>No payment data.</p>
                    </div>
                </div>

                <!-- Payment method cards -->
                <div class="flex flex-col gap-3">
                    <div
                        v-for="(item, idx) in data"
                        :key="item.method"
                        class="flex items-center gap-3 rounded-xl border p-3 transition-colors hover:bg-muted/30"
                    >
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-muted">
                            <component :is="getInfo(item.method).icon" class="h-4 w-4" :class="getInfo(item.method).iconClass" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between">
                                <p class="text-sm font-medium">{{ item.label }}</p>
                                <p class="text-sm font-semibold">{{ formatCurrency(item.total_amount) }}</p>
                            </div>
                            <div class="mt-1.5 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :style="{
                                            width: totalAmount ? `${(item.total_amount / totalAmount) * 100}%` : '0%',
                                            backgroundColor: getColor(item.method, idx),
                                        }"
                                    />
                                </div>
                                <span class="text-xs tabular-nums text-muted-foreground">
                                    {{ totalAmount ? ((item.total_amount / totalAmount) * 100).toFixed(1) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
