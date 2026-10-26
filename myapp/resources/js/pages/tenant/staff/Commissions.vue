<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { BadgePercent, Banknote, Check, Scissors, UserCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InfoTip from '@/components/InfoTip.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
import { usePermissions } from '@/composables/usePermissions';
import { useTenant } from '@/composables/useTenant';
import TenantLayout from '@/layouts/TenantLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Row {
    staff_id: number;
    name: string;
    is_active: boolean;
    default_rate: number;
    services: number;
    sales: number;
    commission: number;
    tips: number;
    due: number;
    payout_id: number | null;
    paid_amount: number | null;
    paid_at: string | null;
}

const props = defineProps<{
    rows: Row[];
    totals: { services: number; sales: number; commission: number; tips: number; due: number };
    filters: { date_from: string; date_to: string; branch_id: number | null };
    branches: { id: number; name: string }[];
}>();

const { tenantUrl } = useTenant();
const { formatCurrency } = useCurrency();
const { can } = usePermissions();
const page = usePage();
const terms = computed(() => (page.props.businessProfile as { terms: { staff_plural: string } } | null)?.terms ?? { staff_plural: 'Staff' });

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Commissions', href: tenantUrl('staff/commissions') },
]);

const from = ref(props.filters.date_from);
const to = ref(props.filters.date_to);
const branch = ref(props.filters.branch_id ? String(props.filters.branch_id) : '');

function apply() {
    router.get(
        tenantUrl('staff/commissions'),
        { date_from: from.value, date_to: to.value, branch_id: branch.value || undefined },
        { preserveState: true, replace: true },
    );
}

// Marking someone paid records a snapshot for exactly the dates on screen, so ask first.
const paying = ref<Row | null>(null);
const payOpen = computed({
    get: () => paying.value !== null,
    set: (v) => { if (!v) paying.value = null; },
});

function confirmPay() {
    if (!paying.value) return;
    router.post(
        tenantUrl('staff/commissions/payouts'),
        { staff_member_id: paying.value.staff_id, date_from: props.filters.date_from, date_to: props.filters.date_to },
        { preserveScroll: true, onFinish: () => (paying.value = null) },
    );
}

const undoing = ref<Row | null>(null);
const undoOpen = computed({
    get: () => undoing.value !== null,
    set: (v) => { if (!v) undoing.value = null; },
});

function confirmUndo() {
    if (!undoing.value?.payout_id) return;
    router.delete(tenantUrl(`staff/commissions/payouts/${undoing.value.payout_id}`), { preserveScroll: true, onFinish: () => (undoing.value = null) });
}
</script>

<template>
    <Head title="Commissions" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <PageHeader
                :icon="BadgePercent"
                title="Commissions"
                :description="`What each of your ${terms.staff_plural.toLowerCase()} earned from the sales credited to them, plus tips.`"
            />

            <form class="flex flex-wrap items-end gap-3" @submit.prevent="apply">
                <div>
                    <Label for="from">From</Label>
                    <Input id="from" v-model="from" type="date" class="mt-1" />
                </div>
                <div>
                    <Label for="to">To</Label>
                    <Input id="to" v-model="to" type="date" class="mt-1" />
                </div>
                <div v-if="branches.length > 1">
                    <Label for="branch">Branch</Label>
                    <select id="branch" v-model="branch" class="mt-1 h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">All branches</option>
                        <option v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                    </select>
                </div>
                <Button type="submit">Apply</Button>
            </form>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard :icon="Scissors" tone="sky" label="Services done" :value="totals.services" />
                <StatCard :icon="Banknote" tone="emerald" label="Sales credited" :value="formatCurrency(totals.sales)" />
                <StatCard :icon="BadgePercent" tone="amber" label="Commission" :value="formatCurrency(totals.commission)" />
                <StatCard :icon="Banknote" tone="purple" label="Commission + tips due" :value="formatCurrency(totals.due)" />
            </div>

            <EmptyState
                v-if="rows.length === 0"
                :icon="UserCheck"
                :title="`No ${terms.staff_plural.toLowerCase()} yet`"
                description="Add people under Staff, then pick one on each line at the POS."
            />

            <div v-else class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-right font-medium">Services</th>
                            <th class="px-4 py-3 text-right font-medium">
                                <span class="inline-flex items-center gap-1.5">Sales<InfoTip text="After refunds and after each sale's discount, before tax. If a line had an assistant, each person gets their percent of it." /></span>
                            </th>
                            <th class="px-4 py-3 text-right font-medium">Commission</th>
                            <th class="px-4 py-3 text-right font-medium">
                                <span class="inline-flex items-center gap-1.5">Tips<InfoTip text="Tips named for the person, plus an equal share of tips left for everyone on a sale." /></span>
                            </th>
                            <th class="px-4 py-3 text-right font-medium">Due</th>
                            <th class="px-4 py-3 text-right font-medium">Payout</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in rows" :key="r.staff_id" class="border-b last:border-0" :class="r.is_active ? '' : 'opacity-60'">
                            <td class="px-4 py-3 font-medium">{{ r.name }}<span v-if="!r.is_active" class="ml-2 text-xs text-muted-foreground">(inactive)</span></td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ r.services }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatCurrency(r.sales) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatCurrency(r.commission) }}<span class="ml-1 text-xs text-muted-foreground">({{ r.default_rate }}%)</span></td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatCurrency(r.tips) }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ formatCurrency(r.due) }}</td>
                            <td class="px-4 py-3 text-right">
                                <template v-if="r.payout_id">
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-teal-700 dark:text-teal-400">
                                        <Check class="size-3.5" /> Paid {{ formatCurrency(r.paid_amount ?? 0) }}
                                    </span>
                                    <Button v-if="can('staff.manage')" variant="link" size="sm" class="h-auto px-2 text-xs" @click="undoing = r">Undo</Button>
                                </template>
                                <Button v-else-if="can('staff.manage') && r.due > 0" size="sm" variant="outline" @click="paying = r">Mark paid</Button>
                                <span v-else class="text-xs text-muted-foreground">-</span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t bg-muted/30 font-semibold">
                        <tr>
                            <td class="px-4 py-3">Total</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ totals.services }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatCurrency(totals.sales) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatCurrency(totals.commission) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatCurrency(totals.tips) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatCurrency(totals.due) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <ConfirmDialog
            v-model:open="payOpen"
            :title="`Mark ${paying?.name ?? ''} as paid?`"
            :description="`This records ${formatCurrency(paying?.due ?? 0)} paid for ${filters.date_from} to ${filters.date_to}. The amount is saved as it is now, and these dates cannot be paid to ${paying?.name ?? 'them'} again.`"
            confirm-label="Mark paid"
            :destructive="false"
            @confirm="confirmPay"
        />

        <ConfirmDialog
            v-model:open="undoOpen"
            :title="`Undo the payout for ${undoing?.name ?? ''}?`"
            description="Use this only if it was recorded by mistake. The sales stay as they are and become unpaid again."
            confirm-label="Undo payout"
            @confirm="confirmUndo"
        />
    </TenantLayout>
</template>
