<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeft, Printer } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import type { Order } from '@/types';

const props = defineProps<{
    order: Order;
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const voidDialog = ref(false);
const voiding = ref(false);

function voidOrder() {
    voiding.value = true;
    router.post(tenantUrl(`orders/${props.order.id}/void`), {}, {
        onFinish: () => {
            voiding.value = false;
            voidDialog.value = false;
        },
    });
}

function printReceipt() {
    window.print();
}

function formatCurrency(amount: string | number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount));
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function paymentMethodLabel(method: string) {
    const labels: Record<string, string> = {
        cash: 'Cash', card: 'Card', e_wallet: 'E-Wallet', bank_transfer: 'Bank Transfer', other: 'Other',
    };
    return labels[method] ?? method;
}

function statusBadgeVariant(status: string) {
    switch (status) {
        case 'completed': return 'default';
        case 'voided': return 'destructive';
        case 'pending': return 'secondary';
        case 'refunded': return 'outline';
        default: return 'secondary';
    }
}

const breadcrumbs = [
    { title: 'Orders', href: tenantUrl('orders') },
    { title: props.order.order_number, href: tenantUrl(`orders/${props.order.id}`) },
];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="icon" as-child>
                        <Link :href="tenantUrl('orders')">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ order.order_number }}</h1>
                        <p class="text-sm text-muted-foreground">{{ formatDate(order.created_at) }}</p>
                    </div>
                    <Badge :variant="statusBadgeVariant(order.status)" class="capitalize">{{ order.status }}</Badge>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="printReceipt">
                        <Printer class="mr-2 h-4 w-4" />
                        Print
                    </Button>
                    <Button
                        v-if="can('pos.void') && order.status === 'completed'"
                        variant="destructive"
                        size="sm"
                        @click="voidDialog = true"
                    >
                        Void Order
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Order Info -->
                <div class="rounded-lg border p-4 space-y-3">
                    <h3 class="font-semibold text-sm">Order Info</h3>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Cashier</span>
                            <span>{{ order.creator?.name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Branch</span>
                            <span>{{ order.branch?.name ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="rounded-lg border p-4 space-y-3">
                    <h3 class="font-semibold text-sm">Customer</h3>
                    <div class="text-sm">
                        <p class="font-medium">{{ order.customer?.name ?? 'Walk-in Customer' }}</p>
                        <p v-if="order.customer?.email" class="text-muted-foreground">{{ order.customer.email }}</p>
                        <p v-if="order.customer?.phone" class="text-muted-foreground">{{ order.customer.phone }}</p>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="rounded-lg border p-4 space-y-3">
                    <h3 class="font-semibold text-sm">Payment</h3>
                    <div v-for="payment in order.payments" :key="payment.id" class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Method</span>
                            <span>{{ paymentMethodLabel(payment.method) }}</span>
                        </div>
                        <div v-if="payment.reference_number" class="flex justify-between">
                            <span class="text-muted-foreground">Reference</span>
                            <span class="font-mono text-xs">{{ payment.reference_number }}</span>
                        </div>
                        <div v-if="payment.amount_tendered" class="flex justify-between">
                            <span class="text-muted-foreground">Tendered</span>
                            <span>{{ formatCurrency(payment.amount_tendered) }}</span>
                        </div>
                        <div v-if="payment.change_amount && Number(payment.change_amount) > 0" class="flex justify-between">
                            <span class="text-muted-foreground">Change</span>
                            <span>{{ formatCurrency(payment.change_amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="rounded-lg border">
                <div class="px-4 py-3 border-b bg-muted/50">
                    <h3 class="font-semibold text-sm">Items</h3>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="px-4 py-3 text-left font-medium">Product</th>
                            <th class="px-4 py-3 text-right font-medium">Price</th>
                            <th class="px-4 py-3 text-center font-medium">Qty</th>
                            <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in order.items" :key="item.id" class="border-b last:border-0">
                            <td class="px-4 py-3">{{ item.product_name }}</td>
                            <td class="px-4 py-3 text-right">{{ formatCurrency(item.product_price) }}</td>
                            <td class="px-4 py-3 text-center">{{ item.quantity }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="flex justify-end">
                <div class="w-72 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Subtotal</span>
                        <span>{{ formatCurrency(order.subtotal) }}</span>
                    </div>
                    <div v-if="Number(order.discount_amount) > 0" class="flex justify-between text-green-600">
                        <span>Discount <span v-if="order.discount_type === 'percentage'" class="text-xs">({{ order.discount_amount }}%)</span></span>
                        <span>-{{ formatCurrency(order.discount_amount) }}</span>
                    </div>
                    <div v-if="Number(order.tax_amount) > 0" class="flex justify-between">
                        <span class="text-muted-foreground">Tax</span>
                        <span>{{ formatCurrency(order.tax_amount) }}</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 text-base font-bold">
                        <span>Total</span>
                        <span>{{ formatCurrency(order.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="order.notes" class="rounded-lg border p-4">
                <h3 class="font-semibold text-sm mb-1">Notes</h3>
                <p class="text-sm text-muted-foreground">{{ order.notes }}</p>
            </div>
        </div>

        <!-- Void Confirmation Dialog -->
        <Dialog v-model:open="voidDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Void Order</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to void order {{ order.order_number }}? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="voidDialog = false">Cancel</Button>
                    <Button variant="destructive" :disabled="voiding" @click="voidOrder">
                        {{ voiding ? 'Voiding...' : 'Void Order' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
