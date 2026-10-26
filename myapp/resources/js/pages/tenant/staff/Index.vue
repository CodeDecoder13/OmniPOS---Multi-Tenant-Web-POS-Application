<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, UserCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import IconAction from '@/components/IconAction.vue';
import InfoTip from '@/components/InfoTip.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useTenant } from '@/composables/useTenant';
import TenantLayout from '@/layouts/TenantLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Staff {
    id: number;
    name: string;
    phone: string | null;
    commission_rate: string;
    is_active: boolean;
}

defineProps<{ staff: Staff[] }>();

const { tenantUrl } = useTenant();
const page = usePage();
const terms = computed(() => (page.props.businessProfile as { terms: { staff: string; staff_plural: string } } | null)?.terms ?? { staff: 'Staff member', staff_plural: 'Staff' });
const showCommission = computed(() => (page.props.branchSettings as Record<string, boolean> | null)?.commissions ?? false);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: terms.value.staff_plural, href: tenantUrl('staff') },
]);

// One dialog for both add and edit.
const open = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({ name: '', phone: '', commission_rate: 0, is_active: true });

function openAdd() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    open.value = true;
}

function openEdit(s: Staff) {
    editingId.value = s.id;
    form.name = s.name;
    form.phone = s.phone ?? '';
    form.commission_rate = Number(s.commission_rate);
    form.is_active = s.is_active;
    form.clearErrors();
    open.value = true;
}

function save() {
    const options = { preserveScroll: true, onSuccess: () => (open.value = false) };
    if (editingId.value) form.put(tenantUrl(`staff/${editingId.value}`), options);
    else form.post(tenantUrl('staff'), options);
}

const deleting = ref<Staff | null>(null);
const deleteOpen = computed({
    get: () => deleting.value !== null,
    set: (v) => { if (!v) deleting.value = null; },
});

function confirmDelete() {
    if (!deleting.value) return;
    router.delete(tenantUrl(`staff/${deleting.value.id}`), { preserveScroll: true, onFinish: () => (deleting.value = null) });
}
</script>

<template>
    <Head :title="terms.staff_plural" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <PageHeader :icon="UserCheck" :title="terms.staff_plural" :description="`The people who do the work on a sale. Pick one on each line at the POS.`">
                <Button @click="openAdd">
                    <Plus class="size-4" /> Add {{ terms.staff.toLowerCase() }}
                </Button>
            </PageHeader>

            <EmptyState
                v-if="staff.length === 0"
                :icon="UserCheck"
                :title="`No ${terms.staff_plural.toLowerCase()} yet`"
                :description="`Add the people who serve your customers. They don't need a login, and they don't count toward your user limit.`"
            >
                <Button @click="openAdd"><Plus class="size-4" /> Add {{ terms.staff.toLowerCase() }}</Button>
            </EmptyState>

            <div v-else class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[520px] text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Phone</th>
                            <th v-if="showCommission" class="px-4 py-3 text-right font-medium">
                                <span class="inline-flex items-center gap-1.5">Commission<InfoTip text="Their default share of what they sell, after discounts and before tax. A service can set its own rate instead." /></span>
                            </th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in staff" :key="s.id" class="border-b last:border-0" :class="s.is_active ? '' : 'opacity-60'">
                            <td class="px-4 py-3 font-medium">{{ s.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ s.phone || '-' }}</td>
                            <td v-if="showCommission" class="px-4 py-3 text-right tabular-nums">{{ Number(s.commission_rate) }}%</td>
                            <td class="px-4 py-3 text-center"><StatusBadge :status="s.is_active ? 'active' : 'inactive'" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <IconAction label="Edit" @click="openEdit(s)"><Pencil class="size-4" /></IconAction>
                                    <IconAction label="Delete" @click="deleting = s"><Trash2 class="size-4 text-destructive" /></IconAction>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Dialog v-model:open="open">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editingId ? `Edit ${terms.staff.toLowerCase()}` : `Add ${terms.staff.toLowerCase()}` }}</DialogTitle>
                    <DialogDescription>They appear in the POS so a sale line can be credited to them.</DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="save">
                    <div>
                        <Label for="staff-name">Name</Label>
                        <Input id="staff-name" v-model="form.name" class="mt-1" required maxlength="120" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <Label for="staff-phone">Phone (optional)</Label>
                        <Input id="staff-phone" v-model="form.phone" class="mt-1" maxlength="30" />
                        <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">{{ form.errors.phone }}</p>
                    </div>
                    <div>
                        <div class="flex items-center gap-1">
                            <Label for="staff-rate">Commission rate (%)</Label>
                            <InfoTip text="Their default share of what they sell. 0 means no commission. Applies only if Commissions is switched on for the branch." />
                        </div>
                        <Input id="staff-rate" v-model.number="form.commission_rate" type="number" min="0" max="100" step="0.5" class="mt-1 w-32" />
                        <p v-if="form.errors.commission_rate" class="mt-1 text-sm text-red-500">{{ form.errors.commission_rate }}</p>
                    </div>
                    <div v-if="editingId" class="flex items-center justify-between rounded-lg border p-3">
                        <div>
                            <Label for="staff-active">Active</Label>
                            <p class="text-xs text-muted-foreground">Inactive people are hidden from the POS but keep their sales history.</p>
                        </div>
                        <Switch id="staff-active" :model-value="form.is_active" @update:model-value="form.is_active = !!$event" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Save' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            v-model:open="deleteOpen"
            :title="`Remove ${deleting?.name ?? ''}?`"
            description="If they already appear on sales they will be deactivated instead, so past sales and commissions stay accurate."
            confirm-label="Remove"
            @confirm="confirmDelete"
        />
    </TenantLayout>
</template>
