<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Eye, Search } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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

function statusBadgeVariant(status: string) {
    switch (status) {
        case 'completed': return 'default';
        case 'voided': return 'destructive';
        case 'pending': return 'secondary';
        case 'refunded': return 'outline';
        default: return 'secondary';
    }
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const breadcrumbs = [{ title: 'Orders', href: tenantUrl('orders') }];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <h1 class="text-2xl font-bold tracking-tight">Orders</h1>

            <div class="flex flex-wrap items-center gap-4">
                <div class="relative max-w-sm flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search order # or customer..." class="pl-9" />
                </div>
                <Select v-model="statusFilter">
                    <SelectTrigger class="w-[160px]">
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
                <Input type="date" v-model="dateFrom" class="w-[160px]" placeholder="From date" />
                <Input type="date" v-model="dateTo" class="w-[160px]" placeholder="To date" />
            </div>

            <div class="rounded-md border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium">Order #</th>
                            <th class="px-4 py-3 text-left font-medium">Customer</th>
                            <th class="px-4 py-3 text-center font-medium">Items</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Date</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders.data" :key="order.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-mono text-xs font-medium">{{ order.order_number }}</td>
                            <td class="px-4 py-3">{{ order.customer?.name ?? 'Walk-in' }}</td>
                            <td class="px-4 py-3 text-center">{{ order.items_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(order.total) }}</td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="statusBadgeVariant(order.status)" class="capitalize">{{ order.status }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground text-xs">{{ formatDate(order.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="tenantUrl(`orders/${order.id}`)">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="orders.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No orders found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="orders.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} orders
                </p>
                <div class="flex gap-1">
                    <template v-for="link in orders.links" :key="link.label">
                        <Button
                            v-if="link.url"
                            variant="outline"
                            size="sm"
                            :class="{ 'bg-primary text-primary-foreground': link.active }"
                            as-child
                        >
                            <Link :href="link.url" v-html="link.label" />
                        </Button>
                        <Button v-else variant="outline" size="sm" disabled v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
