<script setup lang="ts">
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { OperatorPerformanceItem } from '@/types';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps<{
    data: OperatorPerformanceItem[];
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

const chartData = computed(() => ({
    labels: props.data.map((i) => i.user_name),
    datasets: [
        {
            label: 'Revenue',
            data: props.data.map((i) => i.total_revenue),
            backgroundColor: 'rgba(13, 148, 136, 0.7)',
            borderColor: 'rgb(13, 148, 136)',
            borderWidth: 1,
        },
        {
            label: 'Orders',
            data: props.data.map((i) => i.order_count),
            backgroundColor: 'rgba(99, 102, 241, 0.7)',
            borderColor: 'rgb(99, 102, 241)',
            borderWidth: 1,
            yAxisID: 'y1',
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top' as const },
    },
    scales: {
        y: {
            type: 'linear' as const,
            position: 'left' as const,
            title: { display: true, text: 'Revenue' },
        },
        y1: {
            type: 'linear' as const,
            position: 'right' as const,
            title: { display: true, text: 'Orders' },
            grid: { drawOnChartArea: false },
        },
    },
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Operator Performance</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="h-[280px]">
                <Bar v-if="data.length" :data="chartData" :options="chartOptions" />
                <div v-else class="flex h-full items-center justify-center text-muted-foreground">
                    No operator data available.
                </div>
            </div>
            <div v-if="data.length" class="rounded-md border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-2 text-left font-medium">Operator</th>
                            <th class="px-4 py-2 text-right font-medium">Orders</th>
                            <th class="px-4 py-2 text-right font-medium">Revenue</th>
                            <th class="px-4 py-2 text-right font-medium">Avg Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in data" :key="item.user_id" class="border-b last:border-0">
                            <td class="px-4 py-2 font-medium">{{ item.user_name }}</td>
                            <td class="px-4 py-2 text-right">{{ item.order_count }}</td>
                            <td class="px-4 py-2 text-right">{{ formatCurrency(item.total_revenue) }}</td>
                            <td class="px-4 py-2 text-right">{{ formatCurrency(item.avg_order_value) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
