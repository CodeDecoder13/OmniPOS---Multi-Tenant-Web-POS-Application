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
        registrations_trend: { period: string; count: number }[];
        total_users: number;
        verified_users: number;
        unverified_users: number;
    };
    filters: { date_from: string; date_to: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Users Report', href: '/admin/reports/users' },
];

const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

function applyFilters() {
    router.get('/admin/reports/users', { date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true });
}

const regChartOptions = computed(() => ({
    chart: { type: 'area' as const, toolbar: { show: false }, fontFamily: 'inherit' },
    xaxis: { categories: props.report.registrations_trend.map(r => r.period) },
    stroke: { curve: 'smooth' as const, width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
    colors: ['#8b5cf6'],
    dataLabels: { enabled: false },
}));

const regSeries = computed(() => ([
    { name: 'Registrations', data: props.report.registrations_trend.map(r => r.count) },
]));

const verifiedChartOptions = computed(() => ({
    chart: { type: 'donut' as const, fontFamily: 'inherit' },
    labels: ['Verified', 'Unverified'],
    colors: ['#22c55e', '#f59e0b'],
    legend: { position: 'bottom' as const },
}));

const verifiedSeries = computed(() => [props.report.verified_users, props.report.unverified_users]);
</script>

<template>
    <Head title="Users Report" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Users Report</h1>
                <div class="flex gap-4">
                    <div class="rounded-xl border bg-white px-4 py-2 text-center dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-2xl font-bold text-blue-600">{{ report.total_users }}</div>
                        <div class="text-xs text-muted-foreground">Total</div>
                    </div>
                    <div class="rounded-xl border bg-white px-4 py-2 text-center dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-2xl font-bold text-green-600">{{ report.verified_users }}</div>
                        <div class="text-xs text-muted-foreground">Verified</div>
                    </div>
                    <div class="rounded-xl border bg-white px-4 py-2 text-center dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-2xl font-bold text-amber-600">{{ report.unverified_users }}</div>
                        <div class="text-xs text-muted-foreground">Unverified</div>
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
                    <h2 class="mb-4 text-lg font-semibold">Registration Trend</h2>
                    <apexchart type="area" height="320" :options="regChartOptions" :series="regSeries" />
                </div>

                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Verification Breakdown</h2>
                    <apexchart v-if="report.total_users > 0" type="donut" height="280" :options="verifiedChartOptions" :series="verifiedSeries" />
                    <div v-else class="flex h-[280px] items-center justify-center text-sm text-muted-foreground">No data</div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
