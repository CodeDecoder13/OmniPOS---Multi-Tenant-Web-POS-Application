<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTenant } from '@/composables/useTenant';
import type { Shift, ShiftSummary, Order } from '@/types';

interface ShiftOrder {
    id: number;
    order_number: string;
    total: string;
    status: string;
    created_at: string;
    shift_id: number | null;
    payments?: { id: number; order_id: number; method: string; amount: string; status: string }[];
}

const props = defineProps<{
    shift: Shift;
    summary: ShiftSummary;
    orders: ShiftOrder[];
}>();

const { tenantUrl } = useTenant();

function formatCurrency(amount: string | number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount));
}

function formatDate(date: string | null) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatTime(date: string) {
    return new Date(date).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
}

const duration = computed(() => {
    const start = new Date(props.shift.opened_at).getTime();
    const end = props.shift.closed_at ? new Date(props.shift.closed_at).getTime() : Date.now();
    const diff = Math.floor((end - start) / 1000);
    const hours = Math.floor(diff / 3600);
    const minutes = Math.floor((diff % 3600) / 60);
    return `${hours}h ${minutes}m`;
});

const cashDifference = computed(() => Number(props.shift.cash_difference ?? 0));

const differenceColor = computed(() => {
    if (cashDifference.value > 0) return 'text-green-600 dark:text-green-400';
    if (cashDifference.value < 0) return 'text-red-600 dark:text-red-400';
    return 'text-muted-foreground';
});

const differenceLabel = computed(() => {
    if (cashDifference.value > 0) return 'Over';
    if (cashDifference.value < 0) return 'Short';
    return 'Exact';
});

function paymentLabel(method: string): string {
    const labels: Record<string, string> = {
        cash: 'Cash', card: 'Card', e_wallet: 'E-Wallet', bank_transfer: 'Bank Transfer', other: 'Other',
    };
    return labels[method] ?? method;
}

function statusBadgeVariant(status: string) {
    switch (status) {
        case 'completed': return 'default' as const;
        case 'voided': return 'destructive' as const;
        default: return 'secondary' as const;
    }
}

function getOrderPaymentMethod(order: any): string {
    const payment = order.payments?.[0];
    return payment ? paymentLabel(payment.method) : '-';
}

const breadcrumbs = [
    { title: 'Shifts', href: tenantUrl('shifts') },
    { title: `Shift #${props.shift.id}`, href: tenantUrl(`shifts/${props.shift.id}`) },
];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as-child>
                    <Link :href="tenantUrl('shifts')">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Shift #{{ shift.id }}</h1>
                    <p class="text-sm text-muted-foreground">{{ shift.operator?.name ?? 'Unknown' }}</p>
                </div>
                <Badge :variant="shift.status === 'open' ? 'default' : 'secondary'" class="capitalize ml-2">
                    {{ shift.status }}
                </Badge>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Info Card -->
                <div class="rounded-lg border bg-card p-5 space-y-3">
                    <h2 class="font-semibold text-sm">Shift Info</h2>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-muted-foreground">Operator</p>
                            <p class="font-medium">{{ shift.operator?.name ?? 'Unknown' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Branch</p>
                            <p class="font-medium">{{ shift.branch?.name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Opened</p>
                            <p class="font-medium">{{ formatDate(shift.opened_at) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Closed</p>
                            <p class="font-medium">{{ formatDate(shift.closed_at) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Duration</p>
                            <p class="font-medium">{{ duration }}</p>
                        </div>
                    </div>
                    <div v-if="shift.notes" class="pt-2 border-t">
                        <p class="text-muted-foreground text-xs">Notes</p>
                        <p class="text-sm">{{ shift.notes }}</p>
                    </div>
                </div>

                <!-- Cash Reconciliation -->
                <div class="rounded-lg border bg-card p-5 space-y-3">
                    <h2 class="font-semibold text-sm">Cash Reconciliation</h2>
                    <div class="divide-y">
                        <div class="flex justify-between py-2 text-sm">
                            <span class="text-muted-foreground">Starting Cash</span>
                            <span class="font-medium">{{ formatCurrency(shift.starting_cash) }}</span>
                        </div>
                        <div class="flex justify-between py-2 text-sm">
                            <span class="text-muted-foreground">Expected Cash</span>
                            <span class="font-medium">{{ formatCurrency(shift.expected_cash ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between py-2 text-sm">
                            <span class="text-muted-foreground">Actual Cash</span>
                            <span class="font-medium">{{ formatCurrency(shift.ending_cash ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between py-2 text-sm font-semibold">
                            <span>Difference</span>
                            <span :class="differenceColor">
                                {{ formatCurrency(Math.abs(cashDifference)) }}
                                ({{ differenceLabel }})
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats + Payment Breakdown -->
            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg border p-4 text-center">
                        <p class="text-xs text-muted-foreground">Total Sales</p>
                        <p class="text-xl font-bold">{{ formatCurrency(summary.total_sales) }}</p>
                    </div>
                    <div class="rounded-lg border p-4 text-center">
                        <p class="text-xs text-muted-foreground">Orders</p>
                        <p class="text-xl font-bold">{{ summary.total_orders }}</p>
                    </div>
                    <div class="rounded-lg border p-4 text-center">
                        <p class="text-xs text-muted-foreground">Avg. Order Value</p>
                        <p class="text-xl font-bold">{{ formatCurrency(summary.avg_order_value) }}</p>
                    </div>
                    <div class="rounded-lg border p-4 text-center">
                        <p class="text-xs text-muted-foreground">Voided</p>
                        <p class="text-xl font-bold">{{ summary.voided_count }}</p>
                    </div>
                </div>

                <div v-if="summary.payment_breakdown.length > 0" class="rounded-lg border bg-card p-5 space-y-3">
                    <h2 class="font-semibold text-sm">Payment Breakdown</h2>
                    <div class="divide-y">
                        <div
                            v-for="p in summary.payment_breakdown"
                            :key="p.method"
                            class="flex justify-between py-2 text-sm"
                        >
                            <span class="text-muted-foreground">{{ paymentLabel(p.method) }} ({{ p.count }})</span>
                            <span class="font-medium">{{ formatCurrency(p.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="space-y-3">
                <h2 class="text-lg font-semibold">Orders</h2>
                <div class="rounded-md border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-3 text-left font-medium">Order #</th>
                                <th class="px-4 py-3 text-left font-medium">Time</th>
                                <th class="px-4 py-3 text-right font-medium">Total</th>
                                <th class="px-4 py-3 text-center font-medium">Status</th>
                                <th class="px-4 py-3 text-left font-medium">Payment</th>
                                <th class="px-4 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in orders" :key="order.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-mono text-xs font-medium">{{ order.order_number }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ formatTime(order.created_at) }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(order.total) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="statusBadgeVariant(order.status)" class="capitalize">{{ order.status }}</Badge>
                                </td>
                                <td class="px-4 py-3 text-xs">{{ getOrderPaymentMethod(order) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="tenantUrl(`orders/${order.id}`)">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="orders.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">No orders in this shift.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
