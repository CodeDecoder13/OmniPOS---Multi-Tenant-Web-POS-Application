<script setup lang="ts">
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { SalesTrend } from '@/types';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

const props = defineProps<{
    data: SalesTrend;
}>();

const chartData = computed(() => ({
    labels: props.data.labels,
    datasets: [
        {
            label: 'Revenue',
            data: props.data.revenue,
            borderColor: 'rgb(13, 148, 136)',
            backgroundColor: 'rgba(13, 148, 136, 0.1)',
            fill: true,
            tension: 0.3,
            yAxisID: 'y',
        },
        {
            label: 'Orders',
            data: props.data.order_count,
            borderColor: 'rgb(99, 102, 241)',
            backgroundColor: 'rgba(99, 102, 241, 0.5)',
            type: 'bar' as const,
            yAxisID: 'y1',
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
    plugins: {
        legend: {
            position: 'top' as const,
        },
    },
    scales: {
        y: {
            type: 'linear' as const,
            display: true,
            position: 'left' as const,
            title: { display: true, text: 'Revenue' },
        },
        y1: {
            type: 'linear' as const,
            display: true,
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
            <CardTitle>Sales Trend</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="h-[350px]">
                <Line v-if="data.labels.length" :data="chartData" :options="chartOptions" />
                <div v-else class="flex h-full items-center justify-center text-muted-foreground">
                    No data for the selected period.
                </div>
            </div>
        </CardContent>
    </Card>
</template>
