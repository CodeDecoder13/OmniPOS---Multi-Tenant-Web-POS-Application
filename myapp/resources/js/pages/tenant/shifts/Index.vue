<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { CheckCircle, Clock, DollarSign, Eye, PlayCircle, StopCircle, Timer } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Pagination from '@/components/Pagination.vue';
import { useTenant } from '@/composables/useTenant';
import { useCurrency } from '@/composables/useCurrency';
import type { Shift, Branch, PaginatedData } from '@/types';

const props = defineProps<{
    shifts: PaginatedData<Shift>;
    filters: { status?: string; branch_id?: string; date_from?: string; date_to?: string };
    branches: Pick<Branch, 'id' | 'name'>[];
}>();

const { tenantUrl } = useTenant();
const { formatCurrency } = useCurrency();

const statusFilter = ref(props.filters.status ?? 'all');
const branchFilter = ref(props.filters.branch_id ?? 'all');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

function applyFilters() {
    router.get(tenantUrl('shifts'), {
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        branch_id: branchFilter.value !== 'all' ? branchFilter.value : undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

watch([statusFilter, branchFilter, dateFrom, dateTo], applyFilters);

const stats = computed(() => {
    const data = props.shifts.data;
    const open = data.filter(s => s.status === 'open').length;
    const totalSales = data.reduce((sum, s) => sum + Number(s.total_sales), 0);
    return { total: data.length, open, closed: data.length - open, totalSales };
});

function formatDate(date: string | null) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function shiftDuration(shift: Shift): string {
    const start = new Date(shift.opened_at).getTime();
    const end = shift.closed_at ? new Date(shift.closed_at).getTime() : Date.now();
    const diff = Math.floor((end - start) / 1000);
    const hours = Math.floor(diff / 3600);
    const minutes = Math.floor((diff % 3600) / 60);
    return `${hours}h ${minutes}m`;
}

const breadcrumbs = [{ title: 'Shifts', href: tenantUrl('shifts') }];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 text-white">
                        <Timer class="h-4 w-4" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Shifts</h1>
                        <p class="text-sm text-muted-foreground">Monitor cashier shifts and sales</p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <div class="rounded-xl border bg-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-950/50">
                            <Timer class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs text-muted-foreground">Total Shifts</p>
                            <p class="text-lg font-bold tabular-nums">{{ stats.total }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-950/50">
                            <PlayCircle class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs text-muted-foreground">Open</p>
                            <p class="text-lg font-bold tabular-nums">{{ stats.open }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-950/50">
                            <StopCircle class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs text-muted-foreground">Closed</p>
                            <p class="text-lg font-bold tabular-nums">{{ stats.closed }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-950/50">
                            <DollarSign class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs text-muted-foreground">Total Sales</p>
                            <p class="text-lg font-bold tabular-nums">{{ formatCurrency(stats.totalSales) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-xl border bg-card p-3 sm:p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <Select v-model="statusFilter">
                        <SelectTrigger class="w-full sm:w-[160px]">
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="open">Open</SelectItem>
                            <SelectItem value="closed">Closed</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="branchFilter">
                        <SelectTrigger class="w-full sm:w-[180px]">
                            <SelectValue placeholder="All Branches" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Branches</SelectItem>
                            <SelectItem v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <Input type="date" v-model="dateFrom" class="w-full sm:w-[160px]" placeholder="From date" />
                    <Input type="date" v-model="dateTo" class="w-full sm:w-[160px]" placeholder="To date" />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[750px] text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium">Operator</th>
                            <th class="hidden px-4 py-3 text-left font-medium sm:table-cell">Branch</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="hidden px-4 py-3 text-left font-medium md:table-cell">Opened</th>
                            <th class="hidden px-4 py-3 text-left font-medium md:table-cell">Closed</th>
                            <th class="hidden px-4 py-3 text-center font-medium sm:table-cell">Duration</th>
                            <th class="px-4 py-3 text-center font-medium">Orders</th>
                            <th class="px-4 py-3 text-right font-medium">Total Sales</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="shift in shifts.data"
                            :key="shift.id"
                            class="border-b transition-colors last:border-0 hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ shift.operator?.name ?? 'Unknown' }}</div>
                                <div class="text-xs text-muted-foreground sm:hidden">{{ shift.branch?.name ?? '-' }}</div>
                            </td>
                            <td class="hidden px-4 py-3 text-muted-foreground sm:table-cell">{{ shift.branch?.name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    v-if="shift.status === 'open'"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-800"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                    Open
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200 dark:bg-gray-950/50 dark:text-gray-400 dark:ring-gray-700"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400" />
                                    Closed
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 text-xs text-muted-foreground md:table-cell">{{ formatDate(shift.opened_at) }}</td>
                            <td class="hidden px-4 py-3 text-xs text-muted-foreground md:table-cell">{{ formatDate(shift.closed_at) }}</td>
                            <td class="hidden px-4 py-3 text-center text-xs sm:table-cell">{{ shiftDuration(shift) }}</td>
                            <td class="px-4 py-3 text-center tabular-nums">{{ shift.total_orders }}</td>
                            <td class="px-4 py-3 text-right font-medium tabular-nums">{{ formatCurrency(shift.total_sales) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="tenantUrl(`shifts/${shift.id}`)">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="shifts.data.length === 0">
                            <td colspan="9" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Timer class="h-8 w-8 text-muted-foreground/50" />
                                    <p class="text-sm font-medium text-muted-foreground">No shifts found.</p>
                                    <p class="text-xs text-muted-foreground/70">Try adjusting your filters to find what you're looking for.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :data="shifts" />
        </div>
    </TenantLayout>
</template>
