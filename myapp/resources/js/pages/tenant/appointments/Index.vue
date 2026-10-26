<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarClock, ChevronLeft, ChevronRight, Pencil, Plus, ShoppingCart } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCurrency } from '@/composables/useCurrency';
import { usePermissions } from '@/composables/usePermissions';
import { useTenant } from '@/composables/useTenant';
import TenantLayout from '@/layouts/TenantLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Appointment {
    id: number;
    customer_name: string;
    customer_phone: string | null;
    staff_member_id: number | null;
    staff_name: string | null;
    date: string;
    time: string;
    start_label: string;
    end_label: string;
    status: string;
    status_label: string;
    next_steps: { value: string; label: string }[];
    can_edit: boolean;
    notes: string | null;
    order_id: number | null;
    product_ids: number[];
    items: { name: string; price: number; duration_minutes: number }[];
    total: number;
}

const props = defineProps<{
    date: string;
    today: string;
    appointments: Appointment[];
    staff: { id: number; name: string }[];
    services: { id: number; name: string; price: string; duration_minutes: number | null }[];
}>();

const { tenantUrl } = useTenant();
const { formatCurrency } = useCurrency();
const { can } = usePermissions();
const canManage = computed(() => can('appointments.manage'));

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Appointments', href: tenantUrl('appointments') },
]);

function goTo(date: string) {
    router.get(tenantUrl('appointments'), { date }, { preserveScroll: true, preserveState: true });
}

function shiftDay(days: number) {
    const d = new Date(`${props.date}T00:00:00`);
    d.setDate(d.getDate() + days);
    goTo(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`);
}

const dayLabel = computed(() =>
    new Date(`${props.date}T00:00:00`).toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }),
);

// One dialog for both booking and editing.
const open = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({
    customer_name: '',
    customer_phone: '',
    staff_member_id: null as number | null,
    date: '',
    time: '10:00',
    product_ids: [] as number[],
    notes: '',
});

function openAdd() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.date = props.date;
    open.value = true;
}

function openEdit(a: Appointment) {
    editingId.value = a.id;
    form.customer_name = a.customer_name;
    form.customer_phone = a.customer_phone ?? '';
    form.staff_member_id = a.staff_member_id;
    form.date = a.date;
    form.time = a.time;
    form.product_ids = [...a.product_ids];
    form.notes = a.notes ?? '';
    form.clearErrors();
    open.value = true;
}

function save() {
    const options = { preserveScroll: true, onSuccess: () => (open.value = false) };
    if (editingId.value) form.put(tenantUrl(`appointments/${editingId.value}`), options);
    else form.post(tenantUrl('appointments'), options);
}

function toggleService(id: number) {
    form.product_ids = form.product_ids.includes(id) ? form.product_ids.filter((p) => p !== id) : [...form.product_ids, id];
}

const totalMinutes = computed(() =>
    form.product_ids.reduce((sum, id) => sum + (props.services.find((s) => s.id === id)?.duration_minutes ?? 0), 0),
);

function setStatus(a: Appointment, status: string) {
    router.patch(tenantUrl(`appointments/${a.id}/status`), { status }, { preserveScroll: true });
}

function checkOut(a: Appointment) {
    router.visit(tenantUrl(`pos?appointment=${a.id}`));
}

// Each person's column, in time order (the server sorts). Bookings with no person land in "Unassigned".
const byStaff = computed(() => {
    const groups = props.staff.map((s) => ({ id: s.id as number | null, name: s.name, rows: [] as Appointment[] }));
    const other = { id: null as number | null, name: 'Unassigned', rows: [] as Appointment[] };
    for (const a of props.appointments) (groups.find((g) => g.id === a.staff_member_id) ?? other).rows.push(a);
    return [...groups, other].filter((g) => g.rows.length > 0 || g.id !== null);
});
</script>

<template>
    <Head title="Appointments" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <PageHeader :icon="CalendarClock" title="Appointments" description="Book a customer with a staff member, then check them out at the POS when they are done.">
                <Button v-if="canManage" @click="openAdd"><Plus class="size-4" /> Book appointment</Button>
            </PageHeader>

            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" size="icon" aria-label="Previous day" @click="shiftDay(-1)"><ChevronLeft class="size-4" /></Button>
                <Input type="date" :model-value="date" class="w-44" aria-label="Day" @update:model-value="(v) => v && goTo(String(v))" />
                <Button variant="outline" size="icon" aria-label="Next day" @click="shiftDay(1)"><ChevronRight class="size-4" /></Button>
                <Button v-if="date !== today" variant="ghost" @click="goTo(today)">Today</Button>
                <p class="ml-1 text-sm text-muted-foreground">{{ dayLabel }}</p>
            </div>

            <EmptyState
                v-if="staff.length === 0"
                :icon="CalendarClock"
                title="Add your staff first"
                description="Appointments are booked with a person. Add them under People > Staff."
            />

            <EmptyState
                v-else-if="appointments.length === 0"
                :icon="CalendarClock"
                title="Nothing booked this day"
                description="Book a customer to see them here."
            >
                <Button v-if="canManage" @click="openAdd"><Plus class="size-4" /> Book appointment</Button>
            </EmptyState>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <section v-for="g in byStaff" :key="g.id ?? 'none'" class="rounded-xl border bg-card">
                    <h2 class="border-b px-4 py-3 text-sm font-semibold">{{ g.name }} <span class="font-normal text-muted-foreground">({{ g.rows.length }})</span></h2>
                    <p v-if="g.rows.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">Free all day</p>
                    <ul v-else class="divide-y">
                        <li v-for="a in g.rows" :key="a.id" class="space-y-2 p-4" :class="a.status === 'cancelled' || a.status === 'no_show' ? 'opacity-60' : ''">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold tabular-nums">{{ a.start_label }} - {{ a.end_label }}</p>
                                    <p class="truncate text-sm">{{ a.customer_name }}<span v-if="a.customer_phone" class="text-muted-foreground"> · {{ a.customer_phone }}</span></p>
                                </div>
                                <StatusBadge :status="a.status" :label="a.status_label" />
                            </div>
                            <p class="text-sm text-muted-foreground">{{ a.items.map((i) => i.name).join(', ') }} · {{ formatCurrency(a.total) }}</p>
                            <p v-if="a.notes" class="text-xs text-muted-foreground">{{ a.notes }}</p>
                            <div v-if="canManage && a.can_edit" class="flex flex-wrap gap-2 pt-1">
                                <Button size="sm" @click="checkOut(a)"><ShoppingCart class="size-4" /> Check out</Button>
                                <Button v-for="s in a.next_steps" :key="s.value" size="sm" variant="outline" @click="setStatus(a, s.value)">{{ s.label }}</Button>
                                <Button size="sm" variant="ghost" @click="openEdit(a)"><Pencil class="size-4" /> Edit</Button>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        <Dialog v-model:open="open">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingId ? 'Edit appointment' : 'Book appointment' }}</DialogTitle>
                    <DialogDescription>The end time comes from the services' lengths{{ totalMinutes ? ` (${totalMinutes} min)` : '' }}.</DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="save">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label for="ap-name">Customer</Label>
                            <Input id="ap-name" v-model="form.customer_name" class="mt-1" required maxlength="255" />
                            <p v-if="form.errors.customer_name" class="mt-1 text-sm text-red-500">{{ form.errors.customer_name }}</p>
                        </div>
                        <div>
                            <Label for="ap-phone">Phone (optional)</Label>
                            <Input id="ap-phone" v-model="form.customer_phone" class="mt-1" maxlength="50" />
                            <p v-if="form.errors.customer_phone" class="mt-1 text-sm text-red-500">{{ form.errors.customer_phone }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <Label for="ap-staff">With</Label>
                            <select id="ap-staff" v-model="form.staff_member_id" required class="mt-1 h-9 w-full rounded-md border bg-background px-2 text-sm">
                                <option :value="null" disabled>Choose...</option>
                                <option v-for="s in staff" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <p v-if="form.errors.staff_member_id" class="mt-1 text-sm text-red-500">{{ form.errors.staff_member_id }}</p>
                        </div>
                        <div>
                            <Label for="ap-date">Date</Label>
                            <Input id="ap-date" v-model="form.date" type="date" class="mt-1" required />
                            <p v-if="form.errors.date" class="mt-1 text-sm text-red-500">{{ form.errors.date }}</p>
                        </div>
                        <div>
                            <Label for="ap-time">Time</Label>
                            <Input id="ap-time" v-model="form.time" type="time" step="900" class="mt-1" required />
                            <p v-if="form.errors.time" class="mt-1 text-sm text-red-500">{{ form.errors.time }}</p>
                        </div>
                    </div>

                    <fieldset>
                        <legend class="text-sm font-medium">Services</legend>
                        <p v-if="services.length === 0" class="mt-1 text-sm text-muted-foreground">No services yet. Add products of kind Service first.</p>
                        <div class="mt-1 grid max-h-48 gap-1 overflow-y-auto rounded-lg border p-2">
                            <label v-for="s in services" :key="s.id" class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-muted">
                                <input type="checkbox" :checked="form.product_ids.includes(s.id)" @change="toggleService(s.id)" />
                                <span class="flex-1">{{ s.name }}</span>
                                <span class="text-xs text-muted-foreground">{{ s.duration_minutes ? `${s.duration_minutes} min · ` : '' }}{{ formatCurrency(Number(s.price)) }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.product_ids" class="mt-1 text-sm text-red-500">{{ form.errors.product_ids }}</p>
                    </fieldset>

                    <div>
                        <Label for="ap-notes">Notes (optional)</Label>
                        <Input id="ap-notes" v-model="form.notes" class="mt-1" maxlength="1000" />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing || form.product_ids.length === 0">{{ form.processing ? 'Saving...' : 'Save' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
