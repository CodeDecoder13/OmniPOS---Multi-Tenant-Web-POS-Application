<script setup lang="ts">
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
} from 'chart.js';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { PaymentBreakdownItem } from '@/types';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps<{
    data: PaymentBreakdownItem[];
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

const colors = ['#0d9488', '#6366f1', '#f59e0b', '#ef4444', '#8b5cf6'];

const chartData = computed(() => ({
    labels: props.data.map((i) => i.label),
    datasets: [
        {
            data: props.data.map((i) => i.total_amount),
            backgroundColor: colors.slice(0, props.data.length),
            borderWidth: 2,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right' as const,
        },
    },
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Payment Breakdown</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid gap-6 md:grid-cols-2">
                <div class="h-[280px]">
                    <Doughnut v-if="data.length" :data="chartData" :options="chartOptions" />
                    <div v-else class="flex h-full items-center justify-center text-muted-foreground">
                        No payment data available.
                    </div>
                </div>
                <div v-if="data.length" class="rounded-md border self-start">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-2 text-left font-medium">Method</th>
                                <th class="px-4 py-2 text-right font-medium">Count</th>
                                <th class="px-4 py-2 text-right font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in data" :key="item.method" class="border-b last:border-0">
                                <td class="px-4 py-2 font-medium">{{ item.label }}</td>
                                <td class="px-4 py-2 text-right">{{ item.count }}</td>
                                <td class="px-4 py-2 text-right">{{ formatCurrency(item.total_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
