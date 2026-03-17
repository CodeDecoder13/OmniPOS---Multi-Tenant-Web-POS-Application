<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import type { Shift, ShiftSummary } from '@/types/models';

const open = defineModel<boolean>('open', { default: false });

const props = defineProps<{
    shift: Shift | null;
    summary: ShiftSummary | null;
}>();

function formatCurrency(amount: number | string) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount));
}

const cashDifference = computed(() => Number(props.shift?.cash_difference ?? 0));

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
        cash: 'Cash',
        card: 'Card',
        e_wallet: 'E-Wallet',
        bank_transfer: 'Bank Transfer',
        other: 'Other',
    };
    return labels[method] ?? method;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md" @close-auto-focus.prevent>
            <DialogHeader>
                <DialogTitle>Shift Summary</DialogTitle>
            </DialogHeader>

            <div v-if="shift && summary" class="space-y-5 py-2">
                <!-- Sales Summary -->
                <div class="space-y-2">
                    <h3 class="text-sm font-semibold">Sales</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg border p-3 text-center">
                            <p class="text-xs text-muted-foreground">Total Sales</p>
                            <p class="text-lg font-bold">{{ formatCurrency(summary.total_sales) }}</p>
                        </div>
                        <div class="rounded-lg border p-3 text-center">
                            <p class="text-xs text-muted-foreground">Orders</p>
                            <p class="text-lg font-bold">{{ summary.total_orders }}</p>
                        </div>
                        <div class="rounded-lg border p-3 text-center">
                            <p class="text-xs text-muted-foreground">Avg. Order Value</p>
                            <p class="text-lg font-bold">{{ formatCurrency(summary.avg_order_value) }}</p>
                        </div>
                        <div class="rounded-lg border p-3 text-center">
                            <p class="text-xs text-muted-foreground">Voided</p>
                            <p class="text-lg font-bold">{{ summary.voided_count }}</p>
                        </div>
                    </div>
                </div>

                <!-- Cash Reconciliation -->
                <div class="space-y-2">
                    <h3 class="text-sm font-semibold">Cash Reconciliation</h3>
                    <div class="rounded-lg border divide-y">
                        <div class="flex justify-between px-4 py-2 text-sm">
                            <span class="text-muted-foreground">Starting Cash</span>
                            <span class="font-medium">{{ formatCurrency(shift.starting_cash) }}</span>
                        </div>
                        <div class="flex justify-between px-4 py-2 text-sm">
                            <span class="text-muted-foreground">Expected Cash</span>
                            <span class="font-medium">{{ formatCurrency(shift.expected_cash ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between px-4 py-2 text-sm">
                            <span class="text-muted-foreground">Actual Cash</span>
                            <span class="font-medium">{{ formatCurrency(shift.ending_cash ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between px-4 py-2 text-sm font-semibold">
                            <span>Difference</span>
                            <span :class="differenceColor">
                                {{ formatCurrency(Math.abs(cashDifference)) }}
                                ({{ differenceLabel }})
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Payment Breakdown -->
                <div v-if="summary.payment_breakdown.length > 0" class="space-y-2">
                    <h3 class="text-sm font-semibold">Payment Breakdown</h3>
                    <div class="rounded-lg border divide-y">
                        <div
                            v-for="p in summary.payment_breakdown"
                            :key="p.method"
                            class="flex justify-between px-4 py-2 text-sm"
                        >
                            <span class="text-muted-foreground">{{ paymentLabel(p.method) }} ({{ p.count }})</span>
                            <span class="font-medium">{{ formatCurrency(p.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button class="w-full" @click="open = false">Done</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
