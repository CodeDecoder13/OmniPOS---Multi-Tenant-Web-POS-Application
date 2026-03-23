<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    report: {
        plan_distribution: { name: string; count: number }[];
        status_breakdown: { status: string; count: number }[];
        trial_conversions: number;
        total_trials: number;
        conversion_rate: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Subscriptions Report', href: '/admin/reports/subscriptions' },
];

const planChartOptions = computed(() => ({
    chart: { type: 'donut' as const, fontFamily: 'inherit' },
    labels: props.report.plan_distribution.map(p => p.name),
    colors: ['#0d9488', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
    legend: { position: 'bottom' as const },
}));

const planSeries = computed(() => props.report.plan_distribution.map(p => p.count));

const statusChartOptions = computed(() => ({
    chart: { type: 'bar' as const, toolbar: { show: false }, fontFamily: 'inherit' },
    xaxis: { categories: props.report.status_breakdown.map(s => s.status) },
    colors: ['#3b82f6'],
    dataLabels: { enabled: true },
    plotOptions: { bar: { borderRadius: 4, horizontal: true } },
}));

const statusSeries = computed(() => ([
    { name: 'Count', data: props.report.status_breakdown.map(s => s.count) },
]));
</script>

<template>
    <Head title="Subscriptions Report" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">Subscriptions Report</h1>

            <!-- Summary Cards -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-sm text-muted-foreground">Total Trials</p>
                    <p class="mt-1 text-2xl font-bold">{{ report.total_trials }}</p>
                </div>
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-sm text-muted-foreground">Trial Conversions</p>
                    <p class="mt-1 text-2xl font-bold text-green-600">{{ report.trial_conversions }}</p>
                </div>
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-sm text-muted-foreground">Conversion Rate</p>
                    <p class="mt-1 text-2xl font-bold text-teal-600">{{ report.conversion_rate }}%</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Plan Distribution -->
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Plan Distribution</h2>
                    <apexchart v-if="planSeries.some(v => v > 0)" type="donut" height="300" :options="planChartOptions" :series="planSeries" />
                    <div v-else class="flex h-[300px] items-center justify-center text-sm text-muted-foreground">No data</div>

                    <div class="mt-4 space-y-2">
                        <div v-for="plan in report.plan_distribution" :key="plan.name" class="flex justify-between text-sm">
                            <span>{{ plan.name }}</span>
                            <span class="font-medium">{{ plan.count }}</span>
                        </div>
                    </div>
                </div>

                <!-- Status Breakdown -->
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Status Breakdown</h2>
                    <apexchart v-if="report.status_breakdown.length > 0" type="bar" height="300" :options="statusChartOptions" :series="statusSeries" />
                    <div v-else class="flex h-[300px] items-center justify-center text-sm text-muted-foreground">No data</div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
