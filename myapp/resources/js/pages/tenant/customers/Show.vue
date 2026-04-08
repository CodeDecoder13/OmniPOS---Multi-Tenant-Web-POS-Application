<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ShoppingCart, DollarSign, TrendingUp, Calendar } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTenant } from '@/composables/useTenant';
import { useCurrency } from '@/composables/useCurrency';
import type { Customer, CustomerStats, Order, PaginatedData } from '@/types';

const props = defineProps<{
    customer: Customer;
    stats: CustomerStats;
    orders: PaginatedData<Order & { items_count?: number }>;
}>();

const { tenantUrl } = useTenant();
const { formatCurrency } = useCurrency();

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function statusVariant(status: string) {
    switch (status) {
        case 'completed': return 'default';
        case 'voided': return 'destructive';
        case 'refunded': return 'outline';
        default: return 'secondary';
    }
}

const breadcrumbs = [
    { title: 'Customers', href: tenantUrl('customers') },
    { title: props.customer.name, href: tenantUrl(`customers/${props.customer.id}`) },
];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-5xl space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child>
                    <Link :href="tenantUrl('customers')">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ customer.name }}</h1>
                    <div class="flex items-center gap-3 text-sm text-muted-foreground">
                        <span v-if="customer.email">{{ customer.email }}</span>
                        <span v-if="customer.phone">{{ customer.phone }}</span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="rounded-lg border p-4">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <ShoppingCart class="h-4 w-4" />
                        <span>Total Orders</span>
                    </div>
                    <p class="mt-1 text-2xl font-bold">{{ stats.total_orders }}</p>
                </div>
                <div class="rounded-lg border p-4">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <DollarSign class="h-4 w-4" />
                        <span>Total Spent</span>
                    </div>
                    <p class="mt-1 text-2xl font-bold">{{ formatCurrency(stats.total_spent) }}</p>
                </div>
                <div class="rounded-lg border p-4">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <TrendingUp class="h-4 w-4" />
                        <span>Avg Order</span>
                    </div>
                    <p class="mt-1 text-2xl font-bold">{{ formatCurrency(stats.avg_order_value) }}</p>
                </div>
                <div class="rounded-lg border p-4">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Calendar class="h-4 w-4" />
                        <span>Last Visit</span>
                    </div>
                    <p class="mt-1 text-lg font-bold">{{ stats.last_visit ? formatDate(stats.last_visit) : 'Never' }}</p>
                </div>
            </div>

            <!-- Customer Details -->
            <div v-if="customer.address || customer.notes" class="rounded-lg border p-4 space-y-2">
                <h3 class="font-semibold text-sm">Details</h3>
                <div v-if="customer.address" class="text-sm">
                    <span class="text-muted-foreground">Address:</span> {{ customer.address }}
                </div>
                <div v-if="customer.notes" class="text-sm">
                    <span class="text-muted-foreground">Notes:</span> {{ customer.notes }}
                </div>
            </div>

            <!-- Order History -->
            <div class="rounded-lg border">
                <div class="px-4 py-3 border-b bg-muted/50">
                    <h3 class="font-semibold text-sm">Order History</h3>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="px-4 py-3 text-left font-medium">Order #</th>
                            <th class="px-4 py-3 text-left font-medium">Date</th>
                            <th class="px-4 py-3 text-center font-medium">Items</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders.data" :key="order.id" class="border-b last:border-0 hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <Link :href="tenantUrl(`orders/${order.id}`)" class="text-primary hover:underline font-medium">
                                    {{ order.order_number }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ formatDate(order.created_at) }}</td>
                            <td class="px-4 py-3 text-center">{{ order.items_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(order.total) }}</td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="statusVariant(order.status)" class="capitalize text-xs">{{ order.status }}</Badge>
                            </td>
                        </tr>
                        <tr v-if="orders.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No orders yet.</td>
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
