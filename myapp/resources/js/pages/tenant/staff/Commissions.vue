<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { BadgePercent, Banknote, Scissors, UserCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import InfoTip from '@/components/InfoTip.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
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
}

const props = defineProps<{
    rows: Row[];
    totals: { services: number; sales: number; commission: number };
    filters: { date_from: string; date_to: string; branch_id: number | null };
    branches: { id: number; name: string }[];
}>();

const { tenantUrl } = useTenant();
const { formatCurrency } = useCurrency();
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
</script>

<template>
    <Head title="Commissions" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <PageHeader
                :icon="BadgePercent"
                title="Commissions"
                :description="`What each of your ${terms.staff_plural.toLowerCase()} earned from the sales credited to them.`"
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

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <StatCard :icon="Scissors" tone="sky" label="Services done" :value="totals.services" />
                <StatCard :icon="Banknote" tone="emerald" label="Sales credited" :value="formatCurrency(totals.sales)" />
                <StatCard :icon="BadgePercent" tone="amber" label="Commission due" :value="formatCurrency(totals.commission)" />
            </div>

            <EmptyState
                v-if="rows.length === 0"
                :icon="UserCheck"
                :title="`No ${terms.staff_plural.toLowerCase()} yet`"
                description="Add people under Staff, then pick one on each line at the POS."
            />

            <div v-else class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[560px] text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-right font-medium">Services</th>
                            <th class="px-4 py-3 text-right font-medium">
                                <span class="inline-flex items-center gap-1.5">Sales<InfoTip text="After refunds and after each sale's discount, before tax." /></span>
                            </th>
                            <th class="px-4 py-3 text-right font-medium">Default rate</th>
                            <th class="px-4 py-3 text-right font-medium">
                                <span class="inline-flex items-center gap-1.5">Commission<InfoTip text="Sales times the rate. A service with its own rate uses that instead of the person's default." /></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in rows" :key="r.staff_id" class="border-b last:border-0" :class="r.is_active ? '' : 'opacity-60'">
                            <td class="px-4 py-3 font-medium">{{ r.name }}<span v-if="!r.is_active" class="ml-2 text-xs text-muted-foreground">(inactive)</span></td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ r.services }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatCurrency(r.sales) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ r.default_rate }}%</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ formatCurrency(r.commission) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t bg-muted/30 font-semibold">
                        <tr>
                            <td class="px-4 py-3">Total</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ totals.services }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatCurrency(totals.sales) }}</td>
                            <td></td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatCurrency(totals.commission) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </TenantLayout>
</template>
