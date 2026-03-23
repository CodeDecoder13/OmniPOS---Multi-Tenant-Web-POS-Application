<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { computed, ref } from 'vue';

const props = defineProps<{
    report: {
        monthly_revenue: { period: string; revenue: number }[];
        plan_breakdown: { name: string; subscribers: number; revenue: number }[];
        total_mrr: number;
    };
    filters: { date_from: string; date_to: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Revenue Report', href: '/admin/reports/revenue' },
];

const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

function applyFilters() {
    router.get('/admin/reports/revenue', { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true });
}

const chartOptions = computed(() => ({
    chart: { type: 'bar' as const, toolbar: { show: false }, fontFamily: 'inherit' },
    xaxis: { categories: props.report.monthly_revenue.map(r => r.period) },
    yaxis: { labels: { formatter: (val: number) => `₱${val.toLocaleString()}` } },
    colors: ['#0d9488'],
    dataLabels: { enabled: false },
    plotOptions: { bar: { borderRadius: 4 } },
}));

const chartSeries = computed(() => ([
    { name: 'Revenue', data: props.report.monthly_revenue.map(r => r.revenue) },
]));

const planChartOptions = computed(() => ({
    chart: { type: 'donut' as const, fontFamily: 'inherit' },
    labels: props.report.plan_breakdown.map(p => p.name),
    colors: ['#0d9488', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
    legend: { position: 'bottom' as const },
}));

const planSeries = computed(() => props.report.plan_breakdown.map(p => p.revenue));
</script>

<template>
    <Head title="Revenue Report" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Revenue Report</h1>
                <div class="rounded-xl border bg-white px-4 py-2 dark:border-gray-800 dark:bg-gray-900">
                    <span class="text-sm text-muted-foreground">Total MRR: </span>
                    <span class="text-lg font-bold text-teal-600">₱{{ Number(report.total_mrr).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
                </div>
            </div>

            <!-- Date Filters -->
            <div class="flex flex-wrap items-end gap-3">
                <div class="grid gap-1">
                    <Label class="text-xs">From</Label>
                    <Input v-model="dateFrom" type="date" class="w-40" />
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">To</Label>
                    <Input v-model="dateTo" type="date" class="w-40" />
                </div>
                <Button class="bg-teal-600 hover:bg-teal-700" @click="applyFilters">Apply</Button>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Revenue Trend -->
                <div class="lg:col-span-2 rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Monthly Revenue</h2>
                    <apexchart type="bar" height="320" :options="chartOptions" :series="chartSeries" />
                </div>

                <!-- Plan Breakdown -->
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Revenue by Plan</h2>
                    <apexchart v-if="planSeries.some(v => v > 0)" type="donut" height="280" :options="planChartOptions" :series="planSeries" />
                    <div v-else class="flex h-[280px] items-center justify-center text-sm text-muted-foreground">No data</div>

                    <div class="mt-4 space-y-2">
                        <div v-for="plan in report.plan_breakdown" :key="plan.name" class="flex justify-between text-sm">
                            <span>{{ plan.name }} ({{ plan.subscribers }})</span>
                            <span class="font-medium">₱{{ plan.revenue.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
