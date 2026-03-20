<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Edit } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import type { PurchaseOrder } from '@/types';

const props = defineProps<{
    purchaseOrder: PurchaseOrder;
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const receiveDialog = ref(false);
const cancelDialog = ref(false);
const receivedQuantities = ref<Record<number, number>>({});

function initReceive() {
    props.purchaseOrder.items?.forEach(item => {
        receivedQuantities.value[item.id] = item.quantity_ordered - item.quantity_received;
    });
    receiveDialog.value = true;
}

function receive() {
    router.post(tenantUrl(`purchase-orders/${props.purchaseOrder.id}/receive`), {
        received_quantities: receivedQuantities.value,
    }, { onFinish: () => { receiveDialog.value = false; } });
}

function send() {
    router.post(tenantUrl(`purchase-orders/${props.purchaseOrder.id}/send`));
}

function cancel() {
    router.post(tenantUrl(`purchase-orders/${props.purchaseOrder.id}/cancel`), {}, {
        onFinish: () => { cancelDialog.value = false; },
    });
}

function statusVariant(status: string) {
    const map: Record<string, string> = { draft: 'secondary', sent: 'default', partial: 'default', received: 'default', cancelled: 'destructive' };
    return (map[status] ?? 'secondary') as 'default' | 'secondary' | 'destructive';
}

function statusLabel(status: string) {
    const map: Record<string, string> = { draft: 'Draft', sent: 'Sent', partial: 'Partial', received: 'Received', cancelled: 'Cancelled' };
    return map[status] ?? status;
}

const po = props.purchaseOrder;
const breadcrumbs = [
    { title: 'Purchase Orders', href: tenantUrl('purchase-orders') },
    { title: po.po_number, href: tenantUrl(`purchase-orders/${po.id}`) },
];
</script>

<template>
    <TenantLayout title="Purchase Order Details" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ po.po_number }}</h1>
                    <p class="text-sm text-muted-foreground">Created {{ new Date(po.created_at).toLocaleString() }} by {{ po.creator?.name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="statusVariant(po.status)">{{ statusLabel(po.status) }}</Badge>
                    <Button v-if="can('inventory.manage') && po.status === 'draft'" variant="outline" size="sm" as-child>
                        <Link :href="tenantUrl(`purchase-orders/${po.id}/edit`)">
                            <Edit class="mr-1 h-4 w-4" /> Edit
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 rounded-lg border p-4">
                <div>
                    <p class="text-sm text-muted-foreground">Supplier</p>
                    <p class="font-semibold">{{ po.supplier?.name }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Branch</p>
                    <p class="font-semibold">{{ po.branch?.name }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Expected Date</p>
                    <p class="font-semibold">{{ po.expected_date ? new Date(po.expected_date).toLocaleDateString() : '—' }}</p>
                </div>
            </div>

            <div v-if="po.notes" class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Notes</p>
                <p>{{ po.notes }}</p>
            </div>

            <div class="rounded-md border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Product</th>
                            <th class="px-4 py-3 text-left font-medium">SKU</th>
                            <th class="px-4 py-3 text-right font-medium">Ordered</th>
                            <th class="px-4 py-3 text-right font-medium">Received</th>
                            <th class="px-4 py-3 text-right font-medium">Unit Cost</th>
                            <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in po.items" :key="item.id" class="border-b">
                            <td class="px-4 py-3 font-medium">{{ item.product?.name ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ item.product?.sku ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ item.quantity_ordered }}</td>
                            <td class="px-4 py-3 text-right">{{ item.quantity_received }}</td>
                            <td class="px-4 py-3 text-right">{{ Number(item.unit_cost).toFixed(2) }}</td>
                            <td class="px-4 py-3 text-right">{{ Number(item.subtotal).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-muted/50">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right font-semibold">Total</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ Number(po.total_amount).toFixed(2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div v-if="can('inventory.manage')" class="flex gap-3">
                <Button v-if="po.status === 'draft'" @click="send">Send to Supplier</Button>
                <Button v-if="po.status === 'sent' || po.status === 'partial'" @click="initReceive">Receive Items</Button>
                <Button v-if="po.status === 'draft' || po.status === 'sent'" variant="destructive" @click="cancelDialog = true">Cancel PO</Button>
            </div>
        </div>

        <!-- Receive Dialog -->
        <Dialog v-model:open="receiveDialog">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Receive Items</DialogTitle>
                    <DialogDescription>Enter the quantities received for each item.</DialogDescription>
                </DialogHeader>
                <div class="space-y-3">
                    <div v-for="item in po.items" :key="item.id" class="flex items-center gap-3">
                        <span class="flex-1 text-sm">{{ item.product?.name }} ({{ item.quantity_received }}/{{ item.quantity_ordered }} received)</span>
                        <Input v-model="receivedQuantities[item.id]" type="number" min="0" :max="item.quantity_ordered - item.quantity_received" class="w-24" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="receiveDialog = false">Cancel</Button>
                    <Button @click="receive">Confirm Receive</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Cancel Dialog -->
        <Dialog v-model:open="cancelDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cancel Purchase Order</DialogTitle>
                    <DialogDescription>Are you sure you want to cancel this purchase order?</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancelDialog = false">Back</Button>
                    <Button variant="destructive" @click="cancel">Cancel PO</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
