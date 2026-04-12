<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, ChevronRight, Clock, Building2, Pencil, Plus, Table2, Trash2, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { BreadcrumbItem, DayOfWeek, PaginatedData, ShiftSchedule } from '@/types';
import { useTenant } from '@/composables/useTenant';

interface UserOption {
    id: number;
    name: string;
}

interface BranchOption {
    id: number;
    name: string;
}

const ALL_DAYS: { value: DayOfWeek; label: string; short: string }[] = [
    { value: 'mon', label: 'Monday', short: 'Mon' },
    { value: 'tue', label: 'Tuesday', short: 'Tue' },
    { value: 'wed', label: 'Wednesday', short: 'Wed' },
    { value: 'thu', label: 'Thursday', short: 'Thu' },
    { value: 'fri', label: 'Friday', short: 'Fri' },
    { value: 'sat', label: 'Saturday', short: 'Sat' },
    { value: 'sun', label: 'Sunday', short: 'Sun' },
];

const SCHEDULE_COLORS = [
    { bg: 'bg-blue-100 dark:bg-blue-900/40', text: 'text-blue-700 dark:text-blue-300', dot: 'bg-blue-500', border: 'border-blue-200 dark:border-blue-800' },
    { bg: 'bg-emerald-100 dark:bg-emerald-900/40', text: 'text-emerald-700 dark:text-emerald-300', dot: 'bg-emerald-500', border: 'border-emerald-200 dark:border-emerald-800' },
    { bg: 'bg-violet-100 dark:bg-violet-900/40', text: 'text-violet-700 dark:text-violet-300', dot: 'bg-violet-500', border: 'border-violet-200 dark:border-violet-800' },
    { bg: 'bg-amber-100 dark:bg-amber-900/40', text: 'text-amber-700 dark:text-amber-300', dot: 'bg-amber-500', border: 'border-amber-200 dark:border-amber-800' },
    { bg: 'bg-rose-100 dark:bg-rose-900/40', text: 'text-rose-700 dark:text-rose-300', dot: 'bg-rose-500', border: 'border-rose-200 dark:border-rose-800' },
    { bg: 'bg-cyan-100 dark:bg-cyan-900/40', text: 'text-cyan-700 dark:text-cyan-300', dot: 'bg-cyan-500', border: 'border-cyan-200 dark:border-cyan-800' },
    { bg: 'bg-orange-100 dark:bg-orange-900/40', text: 'text-orange-700 dark:text-orange-300', dot: 'bg-orange-500', border: 'border-orange-200 dark:border-orange-800' },
    { bg: 'bg-pink-100 dark:bg-pink-900/40', text: 'text-pink-700 dark:text-pink-300', dot: 'bg-pink-500', border: 'border-pink-200 dark:border-pink-800' },
];

function getUserColor(userId: number) {
    return SCHEDULE_COLORS[userId % SCHEDULE_COLORS.length];
}

const props = defineProps<{
    schedules: PaginatedData<ShiftSchedule>;
    branches: BranchOption[];
    users: UserOption[];
    filters: {
        day: string;
        branch_id: string;
        user_id: string;
    };
}>();

const { tenantUrl } = useTenant();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Schedules', href: tenantUrl('shift-schedules') },
];

// Stats
const stats = computed(() => {
    const data = props.schedules.data;
    const uniqueUsers = new Set(data.map(s => s.user_id));
    const uniqueBranches = new Set(data.filter(s => s.branch_id).map(s => s.branch_id));
    const totalSlots = data.reduce((sum, s) => sum + s.days_of_week.length, 0);
    return {
        total: data.length,
        users: uniqueUsers.size,
        branches: uniqueBranches.size,
        slots: totalSlots,
    };
});

// Calendar data
const schedulesByDay = computed(() => {
    const map: Record<DayOfWeek, ShiftSchedule[]> = {
        mon: [], tue: [], wed: [], thu: [], fri: [], sat: [], sun: [],
    };
    for (const schedule of props.schedules.data) {
        for (const day of schedule.days_of_week) {
            map[day].push(schedule);
        }
    }
    // Sort each day by start_time
    for (const day of Object.keys(map) as DayOfWeek[]) {
        map[day].sort((a, b) => a.start_time.localeCompare(b.start_time));
    }
    return map;
});

// Calendar view mode
const calendarMode = ref<'week' | 'month'>('week');

// Month view
const currentMonth = ref(new Date());

const currentMonthLabel = computed(() => {
    return currentMonth.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

function prevMonth() {
    const d = new Date(currentMonth.value);
    d.setMonth(d.getMonth() - 1);
    currentMonth.value = d;
}

function nextMonth() {
    const d = new Date(currentMonth.value);
    d.setMonth(d.getMonth() + 1);
    currentMonth.value = d;
}

function goToday() {
    currentMonth.value = new Date();
}

interface CalendarDay {
    date: number;
    dayOfWeek: DayOfWeek;
    isCurrentMonth: boolean;
    isToday: boolean;
    schedules: ShiftSchedule[];
}

const JS_DAY_TO_DOW: Record<number, DayOfWeek> = {
    0: 'sun', 1: 'mon', 2: 'tue', 3: 'wed', 4: 'thu', 5: 'fri', 6: 'sat',
};

const monthCalendarDays = computed((): CalendarDay[] => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    const today = new Date();

    // First day of month
    const firstDay = new Date(year, month, 1);
    // Start from Monday: getDay() 0=Sun, we want Mon=0
    let startOffset = firstDay.getDay() - 1;
    if (startOffset < 0) startOffset = 6; // Sunday wraps

    // Last day of month
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();

    // Total cells: fill 6 rows
    const totalCells = Math.max(35, Math.ceil((startOffset + daysInMonth) / 7) * 7);

    const days: CalendarDay[] = [];
    for (let i = 0; i < totalCells; i++) {
        const dateObj = new Date(year, month, 1 - startOffset + i);
        const isCurrentMonth = dateObj.getMonth() === month;
        const dow = JS_DAY_TO_DOW[dateObj.getDay()];
        const isToday = dateObj.getFullYear() === today.getFullYear()
            && dateObj.getMonth() === today.getMonth()
            && dateObj.getDate() === today.getDate();

        // Get schedules for this day of week (recurring weekly)
        const daySchedules = isCurrentMonth ? (schedulesByDay.value[dow] || []) : [];

        days.push({
            date: dateObj.getDate(),
            dayOfWeek: dow,
            isCurrentMonth,
            isToday,
            schedules: daySchedules,
        });
    }
    return days;
});

// Filters
const filterForm = ref({ ...props.filters });

function applyFilters() {
    const params: Record<string, string> = {};
    if (filterForm.value.day) params.day = filterForm.value.day;
    if (filterForm.value.branch_id) params.branch_id = filterForm.value.branch_id;
    if (filterForm.value.user_id) params.user_id = filterForm.value.user_id;
    router.get(tenantUrl('shift-schedules'), params, { preserveState: true });
}

function clearFilters() {
    filterForm.value = { day: '', branch_id: '', user_id: '' };
    router.get(tenantUrl('shift-schedules'), {}, { preserveState: true });
}

// Create/Edit dialog
const dialog = ref(false);
const isEditing = ref(false);
const form = ref({
    id: 0,
    user_id: '',
    branch_id: 'none',
    days_of_week: [] as DayOfWeek[],
    start_time: '',
    end_time: '',
    notes: '',
});
const saving = ref(false);

function toggleDay(day: DayOfWeek) {
    const idx = form.value.days_of_week.indexOf(day);
    if (idx === -1) {
        form.value.days_of_week.push(day);
    } else {
        form.value.days_of_week.splice(idx, 1);
    }
}

function openCreateDialog(prefilledDay?: DayOfWeek) {
    isEditing.value = false;
    form.value = {
        id: 0,
        user_id: '',
        branch_id: 'none',
        days_of_week: prefilledDay ? [prefilledDay] : [],
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
        days_of_week: [...schedule.days_of_week],
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
        days_of_week: form.value.days_of_week,
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

function formatDays(days: DayOfWeek[]): string {
    const order: DayOfWeek[] = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    const labels: Record<DayOfWeek, string> = { mon: 'Mon', tue: 'Tue', wed: 'Wed', thu: 'Thu', fri: 'Fri', sat: 'Sat', sun: 'Sun' };
    return order.filter(d => days.includes(d)).map(d => labels[d]).join(', ');
}

function formatTime(timeStr: string): string {
    const [h, m] = timeStr.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${m} ${ampm}`;
}

function formatTimeShort(timeStr: string): string {
    const [h, m] = timeStr.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'p' : 'a';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${m}${ampm}`;
}
</script>

<template>
    <Head title="Shift Schedules" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-md">
                        <CalendarDays class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Shift Schedules</h1>
                        <p class="text-sm text-muted-foreground">Manage recurring weekly shift assignments</p>
                    </div>
                </div>
                <Button class="w-full sm:w-auto" @click="openCreateDialog()">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Schedule
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                        <CalendarDays class="h-4.5 w-4.5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground">Total Schedules</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-900/40">
                        <Users class="h-4.5 w-4.5 text-teal-600 dark:text-teal-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.users }}</p>
                        <p class="text-xs text-muted-foreground">Users Scheduled</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/40">
                        <Building2 class="h-4.5 w-4.5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.branches }}</p>
                        <p class="text-xs text-muted-foreground">Branches Covered</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/40">
                        <Clock class="h-4.5 w-4.5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.slots }}</p>
                        <p class="text-xs text-muted-foreground">Weekly Slots</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-xl border bg-card p-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:flex md:flex-wrap md:items-end">
                    <div>
                        <Label class="text-xs text-muted-foreground">Day</Label>
                        <Select v-model="filterForm.day">
                            <SelectTrigger class="mt-1 w-full md:w-40">
                                <SelectValue placeholder="All Days" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Days</SelectItem>
                                <SelectItem v-for="d in ALL_DAYS" :key="d.value" :value="d.value">{{ d.label }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label class="text-xs text-muted-foreground">Branch</Label>
                        <Select v-model="filterForm.branch_id">
                            <SelectTrigger class="mt-1 w-full md:w-40">
                                <SelectValue placeholder="All Branches" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Branches</SelectItem>
                                <SelectItem v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label class="text-xs text-muted-foreground">User</Label>
                        <Select v-model="filterForm.user_id">
                            <SelectTrigger class="mt-1 w-full md:w-40">
                                <SelectValue placeholder="All Users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Users</SelectItem>
                                <SelectItem v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex gap-2 sm:col-span-2 md:col-span-1">
                        <Button size="sm" class="flex-1 md:flex-none" @click="applyFilters">Filter</Button>
                        <Button size="sm" variant="outline" class="flex-1 md:flex-none" @click="clearFilters">Clear</Button>
                    </div>
                </div>
            </div>

            <!-- Tabs: Calendar / Table -->
            <Tabs default-value="calendar">
                <TabsList>
                    <TabsTrigger value="calendar" class="gap-1.5">
                        <CalendarDays class="h-4 w-4" />
                        Calendar
                    </TabsTrigger>
                    <TabsTrigger value="table" class="gap-1.5">
                        <Table2 class="h-4 w-4" />
                        Table
                    </TabsTrigger>
                </TabsList>

                <!-- Calendar View -->
                <TabsContent value="calendar" class="mt-4">
                    <!-- Week / Month toggle + Month nav -->
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-1 rounded-lg border bg-card p-1">
                            <button
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="calendarMode === 'week' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="calendarMode = 'week'"
                            >
                                Week
                            </button>
                            <button
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="calendarMode === 'month' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="calendarMode = 'month'"
                            >
                                Month
                            </button>
                        </div>

                        <!-- Month navigation (only in month mode) -->
                        <div v-if="calendarMode === 'month'" class="flex items-center gap-2">
                            <Button variant="outline" size="icon" class="h-8 w-8" @click="prevMonth">
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <button
                                class="min-w-[160px] rounded-md px-3 py-1.5 text-center text-sm font-semibold transition-colors hover:bg-accent"
                                @click="goToday"
                            >
                                {{ currentMonthLabel }}
                            </button>
                            <Button variant="outline" size="icon" class="h-8 w-8" @click="nextMonth">
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Weekly View -->
                    <div v-if="calendarMode === 'week'" class="overflow-x-auto">
                        <div class="grid min-w-[700px] grid-cols-7 gap-px rounded-xl border bg-border">
                            <!-- Day Headers -->
                            <div
                                v-for="day in ALL_DAYS"
                                :key="day.value"
                                class="bg-card px-3 py-2.5 first:rounded-tl-xl last:rounded-tr-xl"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold">{{ day.short }}</span>
                                    <Badge v-if="schedulesByDay[day.value].length > 0" variant="secondary" class="text-xs">
                                        {{ schedulesByDay[day.value].length }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Day Columns -->
                            <div
                                v-for="day in ALL_DAYS"
                                :key="'col-' + day.value"
                                class="flex min-h-[180px] flex-col gap-1.5 bg-card p-2 last:rounded-br-xl first:rounded-bl-xl"
                                :class="{ 'cursor-pointer transition-colors hover:bg-accent/50': schedulesByDay[day.value].length === 0 }"
                                @click="schedulesByDay[day.value].length === 0 ? openCreateDialog(day.value) : undefined"
                            >
                                <!-- Schedule Blocks -->
                                <TooltipProvider v-for="schedule in schedulesByDay[day.value]" :key="schedule.id">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button
                                                class="w-full rounded-lg border p-2 text-left transition-all hover:shadow-md"
                                                :class="[
                                                    getUserColor(schedule.user_id).bg,
                                                    getUserColor(schedule.user_id).border,
                                                ]"
                                                @click.stop="openEditDialog(schedule)"
                                            >
                                                <div class="flex items-center gap-1.5">
                                                    <span
                                                        class="h-2 w-2 shrink-0 rounded-full"
                                                        :class="getUserColor(schedule.user_id).dot"
                                                    />
                                                    <span class="truncate text-xs font-medium" :class="getUserColor(schedule.user_id).text">
                                                        {{ schedule.operator?.name ?? 'Unknown' }}
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-[11px] text-muted-foreground">
                                                    {{ formatTimeShort(schedule.start_time) }} – {{ formatTimeShort(schedule.end_time) }}
                                                </p>
                                                <Badge
                                                    v-if="schedule.branch"
                                                    variant="outline"
                                                    class="mt-1.5 h-4 max-w-full truncate px-1 text-[10px]"
                                                >
                                                    {{ schedule.branch.name }}
                                                </Badge>
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent side="top">
                                            <p class="font-medium">{{ schedule.operator?.name }}</p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ formatTime(schedule.start_time) }} – {{ formatTime(schedule.end_time) }}
                                            </p>
                                            <p v-if="schedule.branch" class="text-xs text-muted-foreground">{{ schedule.branch.name }}</p>
                                            <p v-if="schedule.notes" class="mt-1 text-xs italic">{{ schedule.notes }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <!-- Empty Day Placeholder -->
                                <div
                                    v-if="schedulesByDay[day.value].length === 0"
                                    class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-muted-foreground/25 p-3"
                                >
                                    <div class="text-center">
                                        <Plus class="mx-auto h-4 w-4 text-muted-foreground/40" />
                                        <p class="mt-1 text-[11px] text-muted-foreground/50">Add shift</p>
                                    </div>
                                </div>

                                <!-- Add Button for Non-Empty Days -->
                                <button
                                    v-if="schedulesByDay[day.value].length > 0"
                                    class="mt-auto flex w-full items-center justify-center gap-1 rounded-lg border border-dashed border-muted-foreground/25 py-1.5 text-[11px] text-muted-foreground/50 transition-colors hover:border-primary/40 hover:text-primary"
                                    @click.stop="openCreateDialog(day.value)"
                                >
                                    <Plus class="h-3 w-3" />
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Month View -->
                    <div v-else class="overflow-x-auto">
                        <div class="grid min-w-[700px] grid-cols-7 gap-px rounded-xl border bg-border">
                            <!-- Month Day Headers -->
                            <div
                                v-for="day in ALL_DAYS"
                                :key="'mh-' + day.value"
                                class="bg-card px-3 py-2 text-center first:rounded-tl-xl last:rounded-tr-xl"
                            >
                                <span class="text-xs font-semibold text-muted-foreground">{{ day.short }}</span>
                            </div>

                            <!-- Month Day Cells -->
                            <div
                                v-for="(cell, idx) in monthCalendarDays"
                                :key="'mc-' + idx"
                                class="flex min-h-[80px] flex-col bg-card p-1.5 transition-colors sm:min-h-[110px]"
                                :class="[
                                    cell.isCurrentMonth ? '' : 'opacity-40',
                                    cell.isToday ? 'ring-2 ring-inset ring-primary/50' : '',
                                    cell.isCurrentMonth && cell.schedules.length === 0 ? 'cursor-pointer hover:bg-accent/50' : '',
                                    idx >= monthCalendarDays.length - 7 && idx % 7 === 0 ? 'rounded-bl-xl' : '',
                                    idx >= monthCalendarDays.length - 7 && idx % 7 === 6 ? 'rounded-br-xl' : '',
                                ]"
                                @click="cell.isCurrentMonth && cell.schedules.length === 0 ? openCreateDialog(cell.dayOfWeek) : undefined"
                            >
                                <!-- Date Number -->
                                <div class="mb-1 flex items-center justify-between px-1">
                                    <span
                                        class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium"
                                        :class="cell.isToday ? 'bg-primary text-primary-foreground' : ''"
                                    >
                                        {{ cell.date }}
                                    </span>
                                    <span v-if="cell.schedules.length > 0 && cell.isCurrentMonth" class="text-[10px] text-muted-foreground">
                                        {{ cell.schedules.length }}
                                    </span>
                                </div>

                                <!-- Schedule Chips -->
                                <div v-if="cell.isCurrentMonth" class="flex flex-col gap-0.5">
                                    <template v-for="(schedule, sIdx) in cell.schedules" :key="schedule.id">
                                        <button
                                            v-if="sIdx < 3"
                                            class="w-full truncate rounded px-1.5 py-0.5 text-left text-[11px] font-medium transition-colors hover:shadow-sm"
                                            :class="[getUserColor(schedule.user_id).bg, getUserColor(schedule.user_id).text]"
                                            @click.stop="openEditDialog(schedule)"
                                        >
                                            <span class="flex items-center gap-1">
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="getUserColor(schedule.user_id).dot" />
                                                <span class="truncate">{{ schedule.operator?.name?.split(' ')[0] ?? 'Unknown' }}</span>
                                                <span class="ml-auto shrink-0 text-[10px] text-muted-foreground">{{ formatTimeShort(schedule.start_time) }}</span>
                                            </span>
                                        </button>
                                    </template>
                                    <button
                                        v-if="cell.schedules.length > 3"
                                        class="w-full rounded px-1.5 py-0.5 text-left text-[10px] font-medium text-muted-foreground transition-colors hover:bg-accent"
                                        @click.stop="calendarMode = 'week'"
                                    >
                                        +{{ cell.schedules.length - 3 }} more
                                    </button>
                                </div>

                                <!-- Add on empty current-month cell -->
                                <button
                                    v-if="cell.isCurrentMonth && cell.schedules.length > 0"
                                    class="mt-auto flex w-full items-center justify-center rounded py-0.5 text-[10px] text-muted-foreground/40 transition-colors hover:text-primary"
                                    @click.stop="openCreateDialog(cell.dayOfWeek)"
                                >
                                    <Plus class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                </TabsContent>

                <!-- Table View -->
                <TabsContent value="table" class="mt-4">
                    <div class="overflow-x-auto rounded-xl border bg-card">
                        <table class="w-full min-w-[600px] text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-3 py-3 text-left font-medium sm:px-4">Days</th>
                                    <th class="px-3 py-3 text-left font-medium sm:px-4">User</th>
                                    <th class="px-3 py-3 text-left font-medium sm:px-4">Branch</th>
                                    <th class="px-3 py-3 text-left font-medium sm:px-4">Time</th>
                                    <th class="hidden px-4 py-3 text-left font-medium lg:table-cell">Notes</th>
                                    <th class="px-3 py-3 text-right font-medium sm:px-4">Actions</th>
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
                                    class="border-b last:border-0"
                                >
                                    <td class="px-3 py-3 sm:px-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="day in ALL_DAYS.filter(d => schedule.days_of_week.includes(d.value))"
                                                :key="day.value"
                                                class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                                            >
                                                {{ day.short }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 sm:px-4">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="h-2 w-2 shrink-0 rounded-full"
                                                :class="getUserColor(schedule.user_id).dot"
                                            />
                                            <span class="truncate">{{ schedule.operator?.name ?? 'Unknown' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 sm:px-4">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="schedule.branch
                                                ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'
                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                        >
                                            {{ schedule.branch?.name ?? 'All Branches' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-4">
                                        {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                                    </td>
                                    <td class="hidden max-w-48 truncate px-4 py-3 text-muted-foreground lg:table-cell">{{ schedule.notes ?? '-' }}</td>
                                    <td class="px-3 py-3 text-right sm:px-4">
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
                        <div v-if="schedules.last_page > 1" class="flex flex-col items-center gap-2 border-t px-4 py-3 sm:flex-row sm:justify-between">
                            <p class="text-sm text-muted-foreground">
                                Showing {{ schedules.from }} to {{ schedules.to }} of {{ schedules.total }}
                            </p>
                            <div class="flex flex-wrap justify-center gap-1">
                                <template v-for="link in schedules.links" :key="link.label">
                                    <a
                                        v-if="link.url"
                                        :href="link.url"
                                        class="rounded-md px-3 py-1 text-sm"
                                        :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
                                        v-html="link.label"
                                    />
                                    <span v-else class="px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </TabsContent>
            </Tabs>
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
                        <Label>Days of Week</Label>
                        <div class="mt-1 flex flex-wrap gap-2">
                            <button
                                v-for="d in ALL_DAYS"
                                :key="d.value"
                                type="button"
                                class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="form.days_of_week.includes(d.value)
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                                @click="toggleDay(d.value)"
                            >
                                {{ d.short }}
                            </button>
                        </div>
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
                        <Button type="submit" :disabled="saving || !form.user_id || form.days_of_week.length === 0 || !form.start_time || !form.end_time">
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
                        on {{ scheduleToDelete ? formatDays(scheduleToDelete.days_of_week) : '' }}?
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
