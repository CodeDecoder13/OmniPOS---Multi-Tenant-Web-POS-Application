<script setup lang="ts">
import { computed } from 'vue';
import { DollarSign, Hash, ShoppingBag, TrendingUp } from 'lucide-vue-next';
import type { ReportSummary } from '@/types';

const props = defineProps<{
    summary: ReportSummary;
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

function formatCompact(amount: number) {
    if (amount >= 1_000_000) return `${(amount / 1_000_000).toFixed(1)}M`;
    if (amount >= 1_000) return `${(amount / 1_000).toFixed(1)}K`;
    return amount.toLocaleString();
}

const cards = computed(() => [
    {
        title: 'Total Revenue',
        value: formatCurrency(props.summary.total_revenue),
        icon: DollarSign,
        gradient: 'from-teal-500 to-emerald-600',
        shadow: 'shadow-teal-500/20',
        bg: 'bg-teal-50 dark:bg-teal-950/30',
        iconBg: 'bg-teal-500/10',
        iconColor: 'text-teal-600 dark:text-teal-400',
    },
    {
        title: 'Total Orders',
        value: props.summary.order_count.toLocaleString(),
        icon: ShoppingBag,
        gradient: 'from-indigo-500 to-violet-600',
        shadow: 'shadow-indigo-500/20',
        bg: 'bg-indigo-50 dark:bg-indigo-950/30',
        iconBg: 'bg-indigo-500/10',
        iconColor: 'text-indigo-600 dark:text-indigo-400',
    },
    {
        title: 'Avg Order Value',
        value: formatCurrency(props.summary.avg_order_value),
        icon: TrendingUp,
        gradient: 'from-amber-500 to-orange-600',
        shadow: 'shadow-amber-500/20',
        bg: 'bg-amber-50 dark:bg-amber-950/30',
        iconBg: 'bg-amber-500/10',
        iconColor: 'text-amber-600 dark:text-amber-400',
    },
    {
        title: 'Items Sold',
        value: props.summary.items_sold.toLocaleString(),
        icon: Hash,
        gradient: 'from-rose-500 to-pink-600',
        shadow: 'shadow-rose-500/20',
        bg: 'bg-rose-50 dark:bg-rose-950/30',
        iconBg: 'bg-rose-500/10',
        iconColor: 'text-rose-600 dark:text-rose-400',
    },
]);
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div
            v-for="card in cards"
            :key="card.title"
            class="group relative overflow-hidden rounded-2xl border bg-card p-5 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
        >
            <!-- Gradient accent bar at top -->
            <div
                class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r opacity-80"
                :class="card.gradient"
            />

            <div class="flex items-start justify-between">
                <div class="space-y-2">
                    <p class="text-sm font-medium text-muted-foreground">{{ card.title }}</p>
                    <p class="text-2xl font-bold tracking-tight">{{ card.value }}</p>
                </div>
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-110"
                    :class="[card.iconBg]"
                >
                    <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
                </div>
            </div>
        </div>
    </div>
</template>
