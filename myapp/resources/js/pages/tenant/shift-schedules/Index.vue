<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CalendarDays, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem, PaginatedData, ShiftSchedule } from '@/types';
import { useTenant } from '@/composables/useTenant';

interface UserOption {
    id: number;
    name: string;
}

interface BranchOption {
    id: number;
    name: string;
}

const props = defineProps<{
    schedules: PaginatedData<ShiftSchedule>;
    branches: BranchOption[];
    users: UserOption[];
    filters: {
        date_from: string;
        date_to: string;
        branch_id: string;
        user_id: string;
    };
}>();

const { tenantUrl } = useTenant();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Schedules', href: tenantUrl('shift-schedules') },
];

// Filters
const filterForm = ref({ ...props.filters });

function applyFilters() {
    const params: Record<string, string> = {};
    if (filterForm.value.date_from) params.date_from = filterForm.value.date_from;
    if (filterForm.value.date_to) params.date_to = filterForm.value.date_to;
    if (filterForm.value.branch_id) params.branch_id = filterForm.value.branch_id;
    if (filterForm.value.user_id) params.user_id = filterForm.value.user_id;
    router.get(tenantUrl('shift-schedules'), params, { preserveState: true });
}

function clearFilters() {
    filterForm.value = { date_from: '', date_to: '', branch_id: '', user_id: '' };
    router.get(tenantUrl('shift-schedules'), {}, { preserveState: true });
}

// Create/Edit dialog
const dialog = ref(false);
const isEditing = ref(false);
const form = ref({
    id: 0,
    user_id: '',
    branch_id: 'none',
    scheduled_date: '',
    start_time: '',
    end_time: '',
    notes: '',
});
const saving = ref(false);

function openCreateDialog() {
    isEditing.value = false;
    form.value = {
        id: 0,
        user_id: '',
        branch_id: 'none',
        scheduled_date: '',
        start_time: '',
        end_time: '',
        notes: '',
    };
    dialog.value = true;
}

function openEditDialog(schedule: ShiftSchedule) {
    isEditing.value = true;
    form.value = {
        id: schedule.id,
        user_id: String(schedule.user_id),
        branch_id: schedule.branch_id ? String(schedule.branch_id) : 'none',
        scheduled_date: schedule.scheduled_date.split('T')[0],
        start_time: schedule.start_time.substring(0, 5),
        end_time: schedule.end_time.substring(0, 5),
        notes: schedule.notes ?? '',
    };
    dialog.value = true;
}

function submitForm() {
    saving.value = true;
    const payload = {
        user_id: Number(form.value.user_id),
        branch_id: form.value.branch_id !== 'none' ? Number(form.value.branch_id) : null,
        scheduled_date: form.value.scheduled_date,
        start_time: form.value.start_time,
        end_time: form.value.end_time,
        notes: form.value.notes || null,
    };

    if (isEditing.value) {
        router.put(tenantUrl(`shift-schedules/${form.value.id}`), payload, {
            preserveScroll: true,
            onSuccess: () => { dialog.value = false; },
            onFinish: () => { saving.value = false; },
        });
    } else {
        router.post(tenantUrl('shift-schedules'), payload, {
            preserveScroll: true,
            onSuccess: () => { dialog.value = false; },
            onFinish: () => { saving.value = false; },
        });
    }
}

// Delete
const deleteDialog = ref(false);
const scheduleToDelete = ref<ShiftSchedule | null>(null);
const deleting = ref(false);

function confirmDelete(schedule: ShiftSchedule) {
    scheduleToDelete.value = schedule;
    deleteDialog.value = true;
}

function deleteSchedule() {
    if (!scheduleToDelete.value) return;
    deleting.value = true;
    router.delete(tenantUrl(`shift-schedules/${scheduleToDelete.value.id}`), {
        onFinish: () => {
            deleting.value = false;
            deleteDialog.value = false;
            scheduleToDelete.value = null;
        },
    });
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
}

function formatTime(timeStr: string): string {
    const [h, m] = timeStr.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${m} ${ampm}`;
}
</script>

<template>
    <Head title="Shift Schedules" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Shift Schedules</h1>
                <Button @click="openCreateDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Schedule
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-3 rounded-lg border p-4 dark:border-gray-800">
                <div>
                    <Label class="text-xs">From</Label>
                    <Input v-model="filterForm.date_from" type="date" class="mt-1 w-40" />
                </div>
                <div>
                    <Label class="text-xs">To</Label>
                    <Input v-model="filterForm.date_to" type="date" class="mt-1 w-40" />
                </div>
                <div>
                    <Label class="text-xs">Branch</Label>
                    <Select v-model="filterForm.branch_id">
                        <SelectTrigger class="mt-1 w-40">
                            <SelectValue placeholder="All Branches" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Branches</SelectItem>
                            <SelectItem v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <Label class="text-xs">User</Label>
                    <Select v-model="filterForm.user_id">
                        <SelectTrigger class="mt-1 w-40">
                            <SelectValue placeholder="All Users" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Users</SelectItem>
                            <SelectItem v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex gap-2">
                    <Button size="sm" @click="applyFilters">Filter</Button>
                    <Button size="sm" variant="outline" @click="clearFilters">Clear</Button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Date</th>
                            <th class="px-4 py-3 text-left font-medium">User</th>
                            <th class="px-4 py-3 text-left font-medium">Branch</th>
                            <th class="px-4 py-3 text-left font-medium">Time</th>
                            <th class="px-4 py-3 text-left font-medium">Notes</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="schedules.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                <CalendarDays class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                No schedules found.
                            </td>
                        </tr>
                        <tr
                            v-for="schedule in schedules.data"
                            :key="schedule.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3 font-medium">{{ formatDate(schedule.scheduled_date) }}</td>
                            <td class="px-4 py-3">{{ schedule.operator?.name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="schedule.branch
                                        ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                >
                                    {{ schedule.branch?.name ?? 'All Branches' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                            </td>
                            <td class="px-4 py-3 max-w-48 truncate text-muted-foreground">{{ schedule.notes ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" @click="openEditDialog(schedule)">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="confirmDelete(schedule)">
                                        <Trash2 class="h-4 w-4 text-red-500" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="schedules.last_page > 1" class="flex items-center justify-between border-t px-4 py-3 dark:border-gray-800">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ schedules.from }} to {{ schedules.to }} of {{ schedules.total }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in schedules.links" :key="link.label">
                            <a
                                v-if="link.url"
                                :href="link.url"
                                class="rounded-md px-3 py-1 text-sm"
                                :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Dialog -->
        <Dialog v-model:open="dialog">
            <DialogContent @pointer-down-outside="(e: any) => e.preventDefault()">
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit Schedule' : 'Add Schedule' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? 'Update the shift schedule.' : 'Create a new shift schedule.' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <Label for="schedule-user">User</Label>
                        <Select v-model="form.user_id">
                            <SelectTrigger class="mt-1">
                                <SelectValue placeholder="Select user" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="u in users" :key="u.id" :value="String(u.id)">
                                    {{ u.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label for="schedule-branch">Branch</Label>
                        <Select v-model="form.branch_id">
                            <SelectTrigger class="mt-1">
                                <SelectValue placeholder="All Branches" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">All Branches</SelectItem>
                                <SelectItem v-for="b in branches" :key="b.id" :value="String(b.id)">
                                    {{ b.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label for="schedule-date">Date</Label>
                        <Input id="schedule-date" v-model="form.scheduled_date" type="date" class="mt-1" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label for="schedule-start">Start Time</Label>
                            <Input id="schedule-start" v-model="form.start_time" type="time" class="mt-1" required />
                        </div>
                        <div>
                            <Label for="schedule-end">End Time</Label>
                            <Input id="schedule-end" v-model="form.end_time" type="time" class="mt-1" required />
                        </div>
                    </div>

                    <div>
                        <Label for="schedule-notes">Notes</Label>
                        <Input id="schedule-notes" v-model="form.notes" type="text" placeholder="Optional notes" class="mt-1" />
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="dialog = false">Cancel</Button>
                        <Button type="submit" :disabled="saving || !form.user_id || !form.scheduled_date || !form.start_time || !form.end_time">
                            {{ saving ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Schedule') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Schedule</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this schedule for {{ scheduleToDelete?.operator?.name }}
                        on {{ scheduleToDelete ? formatDate(scheduleToDelete.scheduled_date) : '' }}?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteSchedule" :disabled="deleting">
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
