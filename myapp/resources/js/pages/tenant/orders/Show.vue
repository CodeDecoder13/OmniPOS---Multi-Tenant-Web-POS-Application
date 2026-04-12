<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeft, Printer, Mail, LinkIcon, Copy } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import { usePrinter } from '@/composables/usePrinter';
import { useCurrency } from '@/composables/useCurrency';
import type { ReceiptData } from '@/components/ReceiptTemplate.vue';
import type { Order } from '@/types';
import axios from 'axios';

const props = defineProps<{
    order: Order;
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();
const { printReceipt: doPrintReceipt } = usePrinter();
const { formatCurrency } = useCurrency();
const page = usePage();

const voidDialog = ref(false);
const voiding = ref(false);
const voidReason = ref('');

// Receipt sharing
const emailDialog = ref(false);
const emailAddress = ref('');
const sendingEmail = ref(false);
const emailSent = ref(false);
const receiptLink = ref('');
const linkCopied = ref(false);

async function sendReceiptEmail() {
    sendingEmail.value = true;
    try {
        await axios.post(tenantUrl(`orders/${props.order.id}/receipt/email`), { email: emailAddress.value });
        emailSent.value = true;
        setTimeout(() => {
            emailDialog.value = false;
            emailSent.value = false;
            emailAddress.value = '';
        }, 2000);
    } catch {
        // handled by axios interceptor
    } finally {
        sendingEmail.value = false;
    }
}

async function copyReceiptLink() {
    try {
        const { data } = await axios.get(tenantUrl(`orders/${props.order.id}/receipt/link`));
        receiptLink.value = data.url;
        await navigator.clipboard.writeText(data.url);
        linkCopied.value = true;
        setTimeout(() => { linkCopied.value = false; }, 2000);
    } catch {
        // handled by axios interceptor
    }
}

function voidOrder() {
    voiding.value = true;
    router.post(tenantUrl(`orders/${props.order.id}/void`), { void_reason: voidReason.value }, {
        onFinish: () => {
            voiding.value = false;
            voidDialog.value = false;
            voidReason.value = '';
        },
    });
}

function printReceipt() {
    const o = props.order;
    const tenant = page.props.tenant as any;
    const payment = o.payments?.[0];
    const receiptData: ReceiptData = {
        storeName: tenant?.settings?.store_name || tenant?.name || 'Store',
        storeAddress: tenant?.settings?.store_address,
        storePhone: tenant?.settings?.store_phone,
        receiptHeader: tenant?.settings?.receipt_header,
        receiptFooter: tenant?.settings?.receipt_footer,
        orderNumber: o.order_number,
        dateTime: formatDate(o.created_at),
        cashier: o.creator?.name ?? 'Cashier',
        customer: o.customer?.name ?? null,
        items: (o.items ?? []).map(item => ({
            name: item.product_name,
            quantity: item.quantity,
            price: Number(item.product_price),
            subtotal: Number(item.subtotal),
        })),
        subtotal: Number(o.subtotal),
        discount: Number(o.discount_amount) > 0 ? Number(o.discount_amount) : undefined,
        tax: Number(o.tax_amount) > 0 ? Number(o.tax_amount) : undefined,
        total: Number(o.total),
        tableName: o.table?.name ?? null,
        paymentMethod: payment?.method,
        amountTendered: payment?.amount_tendered ? Number(payment.amount_tendered) : undefined,
        change: payment?.change_amount ? Number(payment.change_amount) : undefined,
        referenceNumber: payment?.reference_number ?? null,
        orderType: o.order_type === 'take_out' ? 'TAKE OUT' : 'DINE IN',
    };
    doPrintReceipt(receiptData, true, {
        logoUrl: tenant?.settings?.receipt_logo_url ?? null,
        showAddress: tenant?.settings?.receipt_show_address !== false,
        showPhone: tenant?.settings?.receipt_show_phone !== false,
        showCustomer: tenant?.settings?.receipt_show_customer !== false,
        showTable: tenant?.settings?.receipt_show_table !== false,
        showOrderType: tenant?.settings?.receipt_show_order_type !== false,
        showTaxBreakdown: tenant?.settings?.receipt_show_tax_breakdown !== false,
        thankYouMessage: (tenant?.settings?.receipt_thank_you_message as string) ?? '',
        width: (tenant?.settings?.receipt_width as '58mm' | '80mm') ?? '80mm',
    });
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
                    <Button v-if="order.receipt_token" variant="outline" size="sm" @click="emailDialog = true">
                        <Mail class="mr-2 h-4 w-4" />
                        Email
                    </Button>
                    <Button v-if="order.receipt_token" variant="outline" size="sm" @click="copyReceiptLink">
                        <Copy class="mr-2 h-4 w-4" />
                        {{ linkCopied ? 'Copied!' : 'Copy Link' }}
                    </Button>
                    <Button
                        v-if="can('pos.void') && order.status === 'completed' && Number(order.refunded_amount) < Number(order.total)"
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <Link :href="tenantUrl(`orders/${order.id}/refund`)">Refund</Link>
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
                            <td class="px-4 py-3">
                                <div>{{ item.product_name }}</div>
                                <p v-if="item.notes" class="text-xs text-muted-foreground italic mt-0.5">Note: {{ item.notes }}</p>
                            </td>
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

            <!-- Void Details -->
            <div v-if="order.status === 'voided'" class="rounded-lg border border-destructive/50 bg-destructive/5 p-4 space-y-2">
                <h3 class="font-semibold text-sm text-destructive">Void Details</h3>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Voided by</span>
                        <span>{{ order.voided_by_user?.name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Voided at</span>
                        <span>{{ order.voided_at ? formatDate(order.voided_at) : '—' }}</span>
                    </div>
                    <div v-if="order.void_reason">
                        <span class="text-muted-foreground">Reason</span>
                        <p class="mt-1">{{ order.void_reason }}</p>
                    </div>
                </div>
            </div>

            <!-- Refund Details -->
            <div v-if="order.refunds && order.refunds.length > 0" class="rounded-lg border border-orange-500/50 bg-orange-500/5 p-4 space-y-3">
                <h3 class="font-semibold text-sm text-orange-600">Refund History</h3>
                <div v-for="refund in order.refunds" :key="refund.id" class="space-y-1 text-sm border-b last:border-0 pb-2 last:pb-0">
                    <div class="flex justify-between">
                        <span class="font-medium">{{ refund.refund_number }}</span>
                        <span class="font-medium text-orange-600">-{{ formatCurrency(refund.amount) }}</span>
                    </div>
                    <div class="flex justify-between text-muted-foreground">
                        <span>{{ refund.type === 'full' ? 'Full Refund' : 'Partial Refund' }}</span>
                        <span>{{ formatDate(refund.created_at) }}</span>
                    </div>
                    <p v-if="refund.reason" class="text-muted-foreground">{{ refund.reason }}</p>
                </div>
                <div class="flex justify-between font-medium text-sm pt-1 border-t">
                    <span>Total Refunded</span>
                    <span class="text-orange-600">{{ formatCurrency(order.refunded_amount) }}</span>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="order.notes" class="rounded-lg border p-4">
                <h3 class="font-semibold text-sm mb-1">Notes</h3>
                <p class="text-sm text-muted-foreground">{{ order.notes }}</p>
            </div>
        </div>

        <!-- Email Receipt Dialog -->
        <Dialog v-model:open="emailDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Email Receipt</DialogTitle>
                    <DialogDescription>Send a receipt for {{ order.order_number }} to an email address.</DialogDescription>
                </DialogHeader>
                <div class="space-y-2 py-2">
                    <Label for="receipt-email">Email Address</Label>
                    <Input id="receipt-email" v-model="emailAddress" type="email" placeholder="customer@example.com" />
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="emailDialog = false">Cancel</Button>
                    <Button :disabled="sendingEmail || !emailAddress.trim() || emailSent" @click="sendReceiptEmail">
                        {{ emailSent ? 'Sent!' : sendingEmail ? 'Sending...' : 'Send' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Void Confirmation Dialog -->
        <Dialog v-model:open="voidDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Void Order</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to void order {{ order.order_number }}? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-2 py-2">
                    <Label for="void-reason">Reason for voiding</Label>
                    <Textarea id="void-reason" v-model="voidReason" placeholder="Enter reason for voiding this order..." rows="3" />
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="voidDialog = false">Cancel</Button>
                    <Button variant="destructive" :disabled="voiding || !voidReason.trim()" @click="voidOrder">
                        {{ voiding ? 'Voiding...' : 'Void Order' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
