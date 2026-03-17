<script setup lang="ts">
import { ref, computed } from 'vue';
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
import { Button } from '@/components/ui/button';
import type { TopProducts } from '@/types';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps<{
    data: TopProducts;
}>();

const mode = ref<'quantity' | 'revenue'>('quantity');

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

const items = computed(() => mode.value === 'quantity' ? props.data.by_quantity : props.data.by_revenue);

const chartData = computed(() => ({
    labels: items.value.map((i) => i.product_name),
    datasets: [
        {
            label: mode.value === 'quantity' ? 'Quantity Sold' : 'Revenue',
            data: items.value.map((i) => mode.value === 'quantity' ? i.total_quantity : i.total_revenue),
            backgroundColor: 'rgba(13, 148, 136, 0.7)',
            borderColor: 'rgb(13, 148, 136)',
            borderWidth: 1,
        },
    ],
}));

const chartOptions = {
    indexAxis: 'y' as const,
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
};
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between">
            <CardTitle>Top Products</CardTitle>
            <div class="flex gap-1">
                <Button
                    size="sm"
                    :variant="mode === 'quantity' ? 'default' : 'outline'"
                    @click="mode = 'quantity'"
                >
                    By Quantity
                </Button>
                <Button
                    size="sm"
                    :variant="mode === 'revenue' ? 'default' : 'outline'"
                    @click="mode = 'revenue'"
                >
                    By Revenue
                </Button>
            </div>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="h-[300px]">
                <Bar v-if="items.length" :data="chartData" :options="chartOptions" />
                <div v-else class="flex h-full items-center justify-center text-muted-foreground">
                    No product data available.
                </div>
            </div>
            <div v-if="items.length" class="rounded-md border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-2 text-left font-medium">#</th>
                            <th class="px-4 py-2 text-left font-medium">Product</th>
                            <th class="px-4 py-2 text-right font-medium">Qty Sold</th>
                            <th class="px-4 py-2 text-right font-medium">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, idx) in items" :key="item.product_name" class="border-b last:border-0">
                            <td class="px-4 py-2 text-muted-foreground">{{ idx + 1 }}</td>
                            <td class="px-4 py-2 font-medium">{{ item.product_name }}</td>
                            <td class="px-4 py-2 text-right">{{ item.total_quantity.toLocaleString() }}</td>
                            <td class="px-4 py-2 text-right">{{ formatCurrency(item.total_revenue) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
