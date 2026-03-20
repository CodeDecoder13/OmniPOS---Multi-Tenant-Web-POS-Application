<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import type { StockTransfer } from '@/types';

const props = defineProps<{
    transfer: StockTransfer;
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const shipDialog = ref(false);
const receiveDialog = ref(false);
const cancelDialog = ref(false);

const sentQuantities = ref<Record<number, number>>({});
const receivedQuantities = ref<Record<number, number>>({});

function initShip() {
    props.transfer.items?.forEach(item => {
        sentQuantities.value[item.id] = item.quantity_requested;
    });
    shipDialog.value = true;
}

function initReceive() {
    props.transfer.items?.forEach(item => {
        receivedQuantities.value[item.id] = item.quantity_sent ?? item.quantity_requested;
    });
    receiveDialog.value = true;
}

function ship() {
    router.post(tenantUrl(`stock-transfers/${props.transfer.id}/ship`), {
        sent_quantities: sentQuantities.value,
    }, { onFinish: () => { shipDialog.value = false; } });
}

function receive() {
    router.post(tenantUrl(`stock-transfers/${props.transfer.id}/receive`), {
        received_quantities: receivedQuantities.value,
    }, { onFinish: () => { receiveDialog.value = false; } });
}

function cancel() {
    router.post(tenantUrl(`stock-transfers/${props.transfer.id}/cancel`), {}, {
        onFinish: () => { cancelDialog.value = false; },
    });
}

function statusVariant(status: string) {
    const map: Record<string, string> = { pending: 'secondary', in_transit: 'default', completed: 'default', cancelled: 'destructive' };
    return (map[status] ?? 'secondary') as 'default' | 'secondary' | 'destructive';
}

function statusLabel(status: string) {
    const map: Record<string, string> = { pending: 'Pending', in_transit: 'In Transit', completed: 'Completed', cancelled: 'Cancelled' };
    return map[status] ?? status;
}

const breadcrumbs = [
    { title: 'Stock Transfers', href: tenantUrl('stock-transfers') },
    { title: props.transfer.transfer_number, href: tenantUrl(`stock-transfers/${props.transfer.id}`) },
];
</script>

<template>
    <TenantLayout title="Stock Transfer Details" :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ transfer.transfer_number }}</h1>
                    <p class="text-sm text-muted-foreground">Created {{ new Date(transfer.created_at).toLocaleString() }} by {{ transfer.creator?.name }}</p>
                </div>
                <Badge :variant="statusVariant(transfer.status)">{{ statusLabel(transfer.status) }}</Badge>
            </div>

            <div class="flex items-center gap-4 rounded-lg border p-4">
                <div class="flex-1">
                    <p class="text-sm text-muted-foreground">From</p>
                    <p class="font-semibold">{{ transfer.source_branch?.name }}</p>
                </div>
                <ArrowRight class="h-5 w-5 text-muted-foreground" />
                <div class="flex-1">
                    <p class="text-sm text-muted-foreground">To</p>
                    <p class="font-semibold">{{ transfer.destination_branch?.name }}</p>
                </div>
            </div>

            <div v-if="transfer.notes" class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Notes</p>
                <p>{{ transfer.notes }}</p>
            </div>

            <div class="rounded-md border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Product</th>
                            <th class="px-4 py-3 text-left font-medium">SKU</th>
                            <th class="px-4 py-3 text-right font-medium">Requested</th>
                            <th class="px-4 py-3 text-right font-medium">Sent</th>
                            <th class="px-4 py-3 text-right font-medium">Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in transfer.items" :key="item.id" class="border-b">
                            <td class="px-4 py-3 font-medium">{{ item.product?.name ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ item.product?.sku ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ item.quantity_requested }}</td>
                            <td class="px-4 py-3 text-right">{{ item.quantity_sent ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ item.quantity_received ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="can('inventory.manage')" class="flex gap-3">
                <Button v-if="transfer.status === 'pending'" @click="initShip">Ship Transfer</Button>
                <Button v-if="transfer.status === 'in_transit'" @click="initReceive">Receive Transfer</Button>
                <Button v-if="transfer.status === 'pending' || transfer.status === 'in_transit'" variant="destructive" @click="cancelDialog = true">Cancel Transfer</Button>
            </div>
        </div>

        <!-- Ship Dialog -->
        <Dialog v-model:open="shipDialog">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Ship Transfer</DialogTitle>
                    <DialogDescription>Confirm quantities being sent.</DialogDescription>
                </DialogHeader>
                <div class="space-y-3">
                    <div v-for="item in transfer.items" :key="item.id" class="flex items-center gap-3">
                        <span class="flex-1 text-sm">{{ item.product?.name }}</span>
                        <Input v-model="sentQuantities[item.id]" type="number" min="0" :max="item.quantity_requested" class="w-24" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="shipDialog = false">Cancel</Button>
                    <Button @click="ship">Confirm Ship</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Receive Dialog -->
        <Dialog v-model:open="receiveDialog">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Receive Transfer</DialogTitle>
                    <DialogDescription>Enter quantities received at destination.</DialogDescription>
                </DialogHeader>
                <div class="space-y-3">
                    <div v-for="item in transfer.items" :key="item.id" class="flex items-center gap-3">
                        <span class="flex-1 text-sm">{{ item.product?.name }}</span>
                        <Input v-model="receivedQuantities[item.id]" type="number" min="0" class="w-24" />
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
                    <DialogTitle>Cancel Transfer</DialogTitle>
                    <DialogDescription>
                        Are you sure? {{ transfer.status === 'in_transit' ? 'Shipped quantities will be reversed to source branch.' : 'This transfer will be cancelled.' }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancelDialog = false">Back</Button>
                    <Button variant="destructive" @click="cancel">Cancel Transfer</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
