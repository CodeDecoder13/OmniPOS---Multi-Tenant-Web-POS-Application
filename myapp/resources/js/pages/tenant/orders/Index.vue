<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { CheckCircle, Clock, DollarSign, Eye, Search, ShoppingCart, XCircle } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Pagination from '@/components/Pagination.vue';
import { useTenant } from '@/composables/useTenant';
import { useCurrency } from '@/composables/useCurrency';
import type { Order, PaginatedData } from '@/types';

const props = defineProps<{
    orders: PaginatedData<Order>;
    filters: { search?: string; status?: string; date_from?: string; date_to?: string };
}>();

const { tenantUrl } = useTenant();
const { formatCurrency } = useCurrency();

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? 'all');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

const stats = computed(() => {
    const data = props.orders.data;
    const completed = data.filter(o => o.status === 'completed').length;
    const pending = data.filter(o => o.status === 'pending').length;
    const revenue = data.filter(o => o.status === 'completed').reduce((sum, o) => sum + Number(o.total), 0);
    return { total: data.length, completed, pending, revenue };
});

function applyFilters() {
    router.get(tenantUrl('orders'), {
        search: search.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
});

watch([statusFilter, dateFrom, dateTo], applyFilters);

function statusDotClass(status: string) {
    const map: Record<string, string> = {
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800',
        pending: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800',
        voided: 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800',
        refunded: 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800',
    };
    return map[status] ?? 'bg-gray-50 text-gray-600 ring-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-700';
}

function statusDot(status: string) {
    const map: Record<string, string> = { completed: 'bg-emerald-500', pending: 'bg-amber-500', voided: 'bg-red-500', refunded: 'bg-blue-500' };
    return map[status] ?? 'bg-gray-400';
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const breadcrumbs = [{ title: 'Orders', href: tenantUrl('orders') }];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 shadow-lg shadow-blue-500/20">
                        <ShoppingCart class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Orders</h1>
                        <p class="text-sm text-muted-foreground">View and manage all transactions</p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <!-- Total Orders -->
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                            <ShoppingCart class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-medium text-muted-foreground">Total Orders</p>
                            <p class="text-lg font-bold tracking-tight">{{ stats.total }}</p>
                        </div>
                    </div>
                </div>
                <!-- Completed -->
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                            <CheckCircle class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-medium text-muted-foreground">Completed</p>
                            <p class="text-lg font-bold tracking-tight">{{ stats.completed }}</p>
                        </div>
                    </div>
                </div>
                <!-- Pending -->
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                            <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-medium text-muted-foreground">Pending</p>
                            <p class="text-lg font-bold tracking-tight">{{ stats.pending }}</p>
                        </div>
                    </div>
                </div>
                <!-- Revenue -->
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                            <DollarSign class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-medium text-muted-foreground">Revenue</p>
                            <p class="text-lg font-bold tracking-tight">{{ formatCurrency(stats.revenue) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-xl border bg-card p-3 sm:p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search order # or customer..." class="pl-9" />
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Select v-model="statusFilter">
                            <SelectTrigger class="w-full sm:w-[160px]">
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="completed">Completed</SelectItem>
                                <SelectItem value="voided">Voided</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="refunded">Refunded</SelectItem>
                            </SelectContent>
                        </Select>
                        <Input type="date" v-model="dateFrom" class="w-full sm:w-[160px]" placeholder="From date" />
                        <Input type="date" v-model="dateTo" class="w-full sm:w-[160px]" placeholder="To date" />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[650px] text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium">Order #</th>
                            <th class="px-4 py-3 text-left font-medium">Customer</th>
                            <th class="px-4 py-3 text-center font-medium">Items</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="hidden px-4 py-3 text-left font-medium sm:table-cell">Date</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders.data" :key="order.id" class="border-b transition-colors last:border-0 hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-medium">{{ order.order_number }}</span>
                                <span class="block text-[11px] text-muted-foreground sm:hidden">{{ formatDate(order.created_at) }}</span>
                            </td>
                            <td class="px-4 py-3">{{ order.customer?.name ?? 'Walk-in' }}</td>
                            <td class="px-4 py-3 text-center tabular-nums">{{ order.items_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-right font-medium tabular-nums">{{ formatCurrency(order.total) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="statusDotClass(order.status)" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset">
                                    <span :class="statusDot(order.status)" class="h-1.5 w-1.5 rounded-full" />
                                    <span class="capitalize">{{ order.status }}</span>
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 text-xs text-muted-foreground sm:table-cell">{{ formatDate(order.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="tenantUrl(`orders/${order.id}`)">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="orders.data.length === 0">
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                                        <ShoppingCart class="h-6 w-6 text-muted-foreground" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-muted-foreground">No orders found</p>
                                        <p class="mt-1 text-sm text-muted-foreground/70">Try adjusting your search or filters.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :data="orders" />
        </div>
    </TenantLayout>
</template>
