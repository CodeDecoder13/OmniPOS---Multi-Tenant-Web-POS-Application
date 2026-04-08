<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { useTenant } from '@/composables/useTenant';
import { useCurrency } from '@/composables/useCurrency';
import type { Order } from '@/types';

const props = defineProps<{
    order: Order;
}>();

const { tenantUrl } = useTenant();
const { formatCurrency } = useCurrency();

const refundType = ref<'full' | 'partial'>('full');
const reason = ref('');
const processing = ref(false);

// Track qty to refund per order item
const itemRefunds = ref<Record<number, number>>(
    Object.fromEntries((props.order.items ?? []).map(item => [item.id, 0]))
);

const maxRefundable = computed(() => Number(props.order.total) - Number(props.order.refunded_amount));

const partialRefundAmount = computed(() => {
    if (refundType.value === 'full') return maxRefundable.value;
    let total = 0;
    for (const item of props.order.items ?? []) {
        const qty = itemRefunds.value[item.id] ?? 0;
        if (qty > 0) {
            total += Number(item.product_price) * qty;
        }
    }
    return Math.min(total, maxRefundable.value);
});

const canSubmit = computed(() => {
    if (!reason.value.trim()) return false;
    if (refundType.value === 'partial') {
        return partialRefundAmount.value > 0;
    }
    return true;
});

function processRefund() {
    processing.value = true;

    const data: Record<string, unknown> = {
        type: refundType.value,
        reason: reason.value,
    };

    if (refundType.value === 'partial') {
        data.items = (props.order.items ?? [])
            .filter(item => (itemRefunds.value[item.id] ?? 0) > 0)
            .map(item => ({
                order_item_id: item.id,
                quantity: itemRefunds.value[item.id],
            }));
    }

    router.post(tenantUrl(`orders/${props.order.id}/refund`), data, {
        onFinish: () => {
            processing.value = false;
        },
    });
}

const breadcrumbs = [
    { title: 'Orders', href: tenantUrl('orders') },
    { title: props.order.order_number, href: tenantUrl(`orders/${props.order.id}`) },
    { title: 'Refund', href: tenantUrl(`orders/${props.order.id}/refund`) },
];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child>
                    <Link :href="tenantUrl(`orders/${order.id}`)">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Refund Order</h1>
                    <p class="text-sm text-muted-foreground">{{ order.order_number }} &mdash; Total: {{ formatCurrency(order.total) }}</p>
                </div>
            </div>

            <div v-if="Number(order.refunded_amount) > 0" class="rounded-lg border border-orange-500/50 bg-orange-500/5 p-3 text-sm">
                Already refunded: <strong class="text-orange-600">{{ formatCurrency(order.refunded_amount) }}</strong>
                &mdash; Remaining: <strong>{{ formatCurrency(maxRefundable) }}</strong>
            </div>

            <!-- Refund Type Toggle -->
            <div class="space-y-2">
                <Label>Refund Type</Label>
                <div class="flex gap-2">
                    <Button
                        :variant="refundType === 'full' ? 'default' : 'outline'"
                        size="sm"
                        @click="refundType = 'full'"
                    >
                        Full Refund
                    </Button>
                    <Button
                        :variant="refundType === 'partial' ? 'default' : 'outline'"
                        size="sm"
                        @click="refundType = 'partial'"
                    >
                        Partial Refund
                    </Button>
                </div>
            </div>

            <!-- Item Selection for Partial -->
            <div v-if="refundType === 'partial'" class="rounded-lg border">
                <div class="px-4 py-3 border-b bg-muted/50">
                    <h3 class="font-semibold text-sm">Select Items to Refund</h3>
                </div>
                <div class="divide-y">
                    <div v-for="item in order.items" :key="item.id" class="flex items-center gap-4 px-4 py-3">
                        <div class="flex-1">
                            <p class="text-sm font-medium">{{ item.product_name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatCurrency(item.product_price) }} x {{ item.quantity }} = {{ formatCurrency(item.subtotal) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Label class="text-xs text-muted-foreground">Qty to refund:</Label>
                            <Input
                                v-model.number="itemRefunds[item.id]"
                                type="number"
                                min="0"
                                :max="item.quantity"
                                class="w-20 h-8 text-sm"
                            />
                            <span class="text-xs text-muted-foreground">/ {{ item.quantity }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reason -->
            <div class="space-y-2">
                <Label for="reason">Reason for Refund</Label>
                <Textarea id="reason" v-model="reason" placeholder="Enter reason for refund..." rows="3" />
            </div>

            <!-- Refund Summary -->
            <div class="rounded-lg border p-4 space-y-2">
                <h3 class="font-semibold text-sm">Refund Summary</h3>
                <div class="flex justify-between text-sm">
                    <span class="text-muted-foreground">Type</span>
                    <span class="capitalize">{{ refundType }} Refund</span>
                </div>
                <div class="flex justify-between text-sm font-bold border-t pt-2">
                    <span>Refund Amount</span>
                    <span class="text-orange-600">{{ formatCurrency(partialRefundAmount) }}</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <Button variant="outline" as-child>
                    <Link :href="tenantUrl(`orders/${order.id}`)">Cancel</Link>
                </Button>
                <Button
                    variant="destructive"
                    :disabled="!canSubmit || processing"
                    @click="processRefund"
                >
                    {{ processing ? 'Processing...' : `Process Refund (${formatCurrency(partialRefundAmount)})` }}
                </Button>
            </div>
        </div>
    </TenantLayout>
</template>
