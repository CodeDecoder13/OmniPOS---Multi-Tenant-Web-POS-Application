<script setup lang="ts">
import { computed } from 'vue';
import { Download } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useTenant } from '@/composables/useTenant';
import { usePrinter } from '@/composables/usePrinter';
import type { KotData } from '@/composables/usePrinter';
import type { Order, KitchenStatus } from '@/types';

const props = defineProps<{
    order: Order;
}>();

const emit = defineEmits<{
    (e: 'update-status', orderId: number, status: KitchenStatus): void;
}>();

const { tenantUrl } = useTenant();
const { printKot } = usePrinter();

const borderColor = computed(() => {
    switch (props.order.kitchen_status) {
        case 'new': return 'border-red-500';
        case 'preparing': return 'border-yellow-500';
        case 'ready': return 'border-green-500';
        default: return 'border-gray-500';
    }
});

const statusBg = computed(() => {
    switch (props.order.kitchen_status) {
        case 'new': return 'bg-red-500/10 text-red-400';
        case 'preparing': return 'bg-yellow-500/10 text-yellow-400';
        case 'ready': return 'bg-green-500/10 text-green-400';
        default: return 'bg-gray-500/10 text-gray-400';
    }
});

const elapsed = computed(() => {
    if (!props.order.kitchen_sent_at) return '';
    const diff = Math.floor((Date.now() - new Date(props.order.kitchen_sent_at).getTime()) / 1000);
    const mins = Math.floor(diff / 60);
    const secs = diff % 60;
    if (mins > 0) return `${mins}m ${secs}s`;
    return `${secs}s`;
});

const nextAction = computed<{ label: string; status: KitchenStatus; variant: 'default' | 'secondary' | 'destructive' } | null>(() => {
    switch (props.order.kitchen_status) {
        case 'new': return { label: 'Start', status: 'preparing', variant: 'default' };
        case 'preparing': return { label: 'Ready', status: 'ready', variant: 'default' };
        case 'ready': return { label: 'Served', status: 'served', variant: 'secondary' };
        default: return null;
    }
});

function handleAction() {
    if (nextAction.value) {
        emit('update-status', props.order.id, nextAction.value.status);
    }
}

function handlePrintKot() {
    const kotData: KotData = {
        orderNumber: props.order.order_number,
        dateTime: new Date(props.order.created_at).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }),
        tableName: props.order.table?.name ?? null,
        orderType: props.order.order_type === 'take_out' ? 'TAKE OUT' : 'DINE IN',
        items: (props.order.items ?? []).map(item => ({
            name: item.product_name,
            quantity: item.quantity,
            variations: item.variations?.map(v => ({
                group_name: v.variation_group_name,
                option_name: v.option_name,
            })),
            addons: item.item_addons?.map(a => ({
                name: a.addon_name,
            })),
        })),
        notes: props.order.notes,
        kitchenNotes: props.order.kitchen_notes,
    };
    printKot(kotData);
}

function downloadKotPdf() {
    window.open(tenantUrl(`kitchen/${props.order.id}/kot/pdf`), '_blank');
}
</script>

<template>
    <div
        :class="[
            'rounded-lg border-2 bg-gray-800 p-4 flex flex-col gap-3 transition-colors',
            borderColor,
        ]"
    >
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-lg font-bold text-white">#{{ order.order_number.split('-').pop() }}</span>
                <Badge :class="statusBg" class="uppercase text-[10px] font-bold">
                    {{ order.kitchen_status }}
                </Badge>
            </div>
            <span v-if="elapsed" class="text-sm font-mono text-gray-400">{{ elapsed }}</span>
        </div>

        <!-- Table / Order type -->
        <div class="flex items-center gap-2 text-sm">
            <span v-if="order.table" class="font-semibold text-white">Table: {{ order.table.name }}</span>
            <Badge variant="outline" class="text-[10px] text-gray-300 border-gray-600">
                {{ order.order_type === 'take_out' ? 'TAKE OUT' : 'DINE IN' }}
            </Badge>
        </div>

        <!-- Items -->
        <div class="flex-1 space-y-1.5">
            <div v-for="item in order.items" :key="item.id" class="text-sm">
                <div class="flex gap-2">
                    <span class="font-bold text-white min-w-[28px]">{{ item.quantity }}x</span>
                    <div class="flex-1">
                        <span class="text-white font-medium">{{ item.product_name }}</span>
                        <div v-if="item.variations?.length" class="text-xs text-gray-400">
                            <span v-for="v in item.variations" :key="v.id" class="block pl-2">
                                - {{ v.variation_group_name }}: {{ v.option_name }}
                            </span>
                        </div>
                        <div v-if="item.item_addons?.length" class="text-xs text-gray-400">
                            <span v-for="a in item.item_addons" :key="a.id" class="block pl-2">
                                + {{ a.addon_name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div v-if="order.notes" class="rounded bg-gray-700 px-2 py-1.5 text-xs text-yellow-300">
            <span class="font-bold">Note:</span> {{ order.notes }}
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 pt-1">
            <Button
                v-if="nextAction"
                :variant="nextAction.variant"
                size="sm"
                class="flex-1"
                @click="handleAction"
            >
                {{ nextAction.label }}
            </Button>
            <Button variant="ghost" size="icon" class="h-8 w-8 text-gray-400 hover:text-white" @click="handlePrintKot" title="Print KOT">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z" />
                </svg>
            </Button>
            <Button variant="ghost" size="icon" class="h-8 w-8 text-gray-400 hover:text-white" @click="downloadKotPdf" title="Download KOT PDF">
                <Download class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
