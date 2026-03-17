<script setup lang="ts">
import { DollarSign, Hash, ShoppingBag, TrendingUp } from 'lucide-vue-next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { ReportSummary } from '@/types';

defineProps<{
    summary: ReportSummary;
}>();

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                <DollarSign class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatCurrency(summary.total_revenue) }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Orders</CardTitle>
                <ShoppingBag class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ summary.order_count.toLocaleString() }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Avg Order Value</CardTitle>
                <TrendingUp class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatCurrency(summary.avg_order_value) }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Items Sold</CardTitle>
                <Hash class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ summary.items_sold.toLocaleString() }}</div>
            </CardContent>
        </Card>
    </div>
</template>
