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
        signups_trend: { period: string; count: number }[];
        business_types: { type: string; count: number }[];
        active_count: number;
        inactive_count: number;
        total: number;
    };
    filters: { date_from: string; date_to: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Tenants Report', href: '/admin/reports/tenants' },
];

const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

function applyFilters() {
    router.get('/admin/reports/tenants', { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true });
}

const signupsChartOptions = computed(() => ({
    chart: { type: 'bar' as const, toolbar: { show: false }, fontFamily: 'inherit' },
    xaxis: { categories: props.report.signups_trend.map(r => r.period) },
    colors: ['#3b82f6'],
    dataLabels: { enabled: false },
    plotOptions: { bar: { borderRadius: 4 } },
}));

const signupsSeries = computed(() => ([
    { name: 'Signups', data: props.report.signups_trend.map(r => r.count) },
]));

const typeChartOptions = computed(() => ({
    chart: { type: 'donut' as const, fontFamily: 'inherit' },
    labels: props.report.business_types.map(t => t.type),
    colors: ['#0d9488', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'],
    legend: { position: 'bottom' as const },
}));

const typeSeries = computed(() => props.report.business_types.map(t => t.count));

const statusChartOptions = computed(() => ({
    chart: { type: 'donut' as const, fontFamily: 'inherit' },
    labels: ['Active', 'Inactive'],
    colors: ['#22c55e', '#ef4444'],
    legend: { position: 'bottom' as const },
}));

const statusSeries = computed(() => [props.report.active_count, props.report.inactive_count]);
</script>

<template>
    <Head title="Tenants Report" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Tenants Report</h1>
                <div class="flex gap-4">
                    <div class="rounded-xl border bg-white px-4 py-2 text-center dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-2xl font-bold text-teal-600">{{ report.total }}</div>
                        <div class="text-xs text-muted-foreground">Total</div>
                    </div>
                    <div class="rounded-xl border bg-white px-4 py-2 text-center dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-2xl font-bold text-green-600">{{ report.active_count }}</div>
                        <div class="text-xs text-muted-foreground">Active</div>
                    </div>
                    <div class="rounded-xl border bg-white px-4 py-2 text-center dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-2xl font-bold text-red-600">{{ report.inactive_count }}</div>
                        <div class="text-xs text-muted-foreground">Inactive</div>
                    </div>
                </div>
            </div>

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
                <div class="lg:col-span-2 rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Signups Trend</h2>
                    <apexchart type="bar" height="320" :options="signupsChartOptions" :series="signupsSeries" />
                </div>

                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="mb-4 text-lg font-semibold">Business Types</h2>
                        <apexchart v-if="typeSeries.some(v => v > 0)" type="donut" height="200" :options="typeChartOptions" :series="typeSeries" />
                        <div v-else class="flex h-[200px] items-center justify-center text-sm text-muted-foreground">No data</div>
                    </div>

                    <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                        <h2 class="mb-4 text-lg font-semibold">Active vs Inactive</h2>
                        <apexchart v-if="report.total > 0" type="donut" height="200" :options="statusChartOptions" :series="statusSeries" />
                        <div v-else class="flex h-[200px] items-center justify-center text-sm text-muted-foreground">No data</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
