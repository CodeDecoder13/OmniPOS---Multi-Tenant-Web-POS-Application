<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { CheckCircle, Edit, Grid3X3, Armchair, Plus, Search, Trash2, Users, Wrench, XCircle } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Pagination from '@/components/Pagination.vue';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import type { Table, PaginatedData, Branch } from '@/types';

const props = defineProps<{
    tables: PaginatedData<Table>;
    branches: Pick<Branch, 'id' | 'name'>[];
    filters: { search?: string; status?: string; branch_id?: string };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

// Stats
const stats = computed(() => {
    const data = props.tables.data;
    const available = data.filter(t => t.status === 'available').length;
    const occupied = data.filter(t => t.status === 'occupied').length;
    const totalCapacity = data.reduce((sum, t) => sum + t.capacity, 0);
    return { total: data.length, available, occupied, totalCapacity };
});

// Filters
const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilters({ search: value || undefined });
    }, 300);
});

function onStatusChange(value: string | number | boolean | Record<string, string>) {
    const v = String(value);
    statusFilter.value = v;
    applyFilters({ status: v === 'all' ? undefined : v });
}

function applyFilters(overrides: Record<string, unknown> = {}) {
    router.get(tenantUrl('tables'), {
        search: search.value || undefined,
        status: statusFilter.value === 'all' ? undefined : statusFilter.value || undefined,
        ...overrides,
    }, { preserveState: true, replace: true });
}

// Delete dialog
const deleteDialog = ref(false);
const tableToDelete = ref<Table | null>(null);

function confirmDelete(table: Table) {
    tableToDelete.value = table;
    deleteDialog.value = true;
}

function deleteTable() {
    if (!tableToDelete.value) return;
    router.delete(tenantUrl(`tables/${tableToDelete.value.id}`), {
        onFinish: () => {
            deleteDialog.value = false;
            tableToDelete.value = null;
        },
    });
}

// Status helpers
function statusLabel(status: string) {
    return status.charAt(0).toUpperCase() + status.slice(1);
}

function statusBadgeClasses(status: string) {
    const map: Record<string, string> = {
        available: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800',
        occupied: 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800',
        reserved: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800',
        maintenance: 'bg-gray-50 text-gray-600 ring-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-700',
    };
    return map[status] ?? 'bg-gray-50 text-gray-600 ring-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-700';
}

function statusDotClass(status: string) {
    const map: Record<string, string> = {
        available: 'bg-emerald-500',
        occupied: 'bg-red-500',
        reserved: 'bg-amber-500',
        maintenance: 'bg-gray-400',
    };
    return map[status] ?? 'bg-gray-400';
}

const breadcrumbs = [{ title: 'Tables', href: tenantUrl('tables') }];
</script>

<template>
    <TenantLayout title="Tables" :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-md">
                        <Grid3X3 class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Tables</h1>
                        <p class="text-sm text-muted-foreground">Manage your restaurant tables and seating</p>
                    </div>
                </div>
                <Button v-if="can('tables.create')" class="w-full sm:w-auto" as-child>
                    <Link :href="tenantUrl('tables/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Table
                    </Link>
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                        <Grid3X3 class="h-4.5 w-4.5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground">Total Tables</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                        <CheckCircle class="h-4.5 w-4.5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.available }}</p>
                        <p class="text-xs text-muted-foreground">Available</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/40">
                        <Armchair class="h-4.5 w-4.5 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.occupied }}</p>
                        <p class="text-xs text-muted-foreground">Occupied</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/40">
                        <Users class="h-4.5 w-4.5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.totalCapacity }}</p>
                        <p class="text-xs text-muted-foreground">Total Seats</p>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-xl border bg-card p-3 sm:p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1 min-w-0">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search tables..." class="pl-9" />
                    </div>
                    <Select :model-value="statusFilter || 'all'" @update:model-value="onStatusChange">
                        <SelectTrigger class="w-full sm:w-[160px]">
                            <SelectValue placeholder="All Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Status</SelectItem>
                            <SelectItem value="available">Available</SelectItem>
                            <SelectItem value="occupied">Occupied</SelectItem>
                            <SelectItem value="reserved">Reserved</SelectItem>
                            <SelectItem value="maintenance">Maintenance</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="tables.data.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border bg-card py-16"
            >
                <div class="rounded-full bg-muted p-4">
                    <Grid3X3 class="h-8 w-8 text-muted-foreground" />
                </div>
                <h3 class="mt-4 text-lg font-semibold">No tables found</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ search || statusFilter ? 'Try adjusting your filters.' : 'Create your first table to get started.' }}
                </p>
                <Button v-if="can('tables.create') && !search && !statusFilter" class="mt-4" as-child>
                    <Link :href="tenantUrl('tables/create')">Create Table</Link>
                </Button>
            </div>

            <!-- Table -->
            <div v-else>
                <div class="overflow-x-auto rounded-xl border bg-card">
                    <table class="w-full min-w-[550px] text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-3 py-3 text-left font-medium sm:px-4">Name</th>
                                <th class="hidden px-3 py-3 text-left font-medium sm:table-cell sm:px-4">Branch</th>
                                <th class="px-3 py-3 text-center font-medium sm:px-4">Capacity</th>
                                <th class="px-3 py-3 text-center font-medium sm:px-4">Status</th>
                                <th class="hidden px-3 py-3 text-center font-medium md:table-cell sm:px-4">Active</th>
                                <th class="px-3 py-3 text-right font-medium sm:px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="table in tables.data"
                                :key="table.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/30"
                            >
                                <!-- Name + branch on mobile -->
                                <td class="px-3 py-3 sm:px-4">
                                    <p class="font-medium">{{ table.name }}</p>
                                    <p class="text-xs text-muted-foreground sm:hidden">
                                        {{ table.branch?.name ?? 'All Branches' }}
                                    </p>
                                </td>
                                <!-- Branch -->
                                <td class="hidden px-3 py-3 sm:table-cell sm:px-4">
                                    {{ table.branch?.name ?? '—' }}
                                </td>
                                <!-- Capacity -->
                                <td class="px-3 py-3 text-center tabular-nums sm:px-4">{{ table.capacity }}</td>
                                <!-- Status -->
                                <td class="px-3 py-3 text-center sm:px-4">
                                    <span
                                        :class="statusBadgeClasses(table.status)"
                                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    >
                                        <span :class="statusDotClass(table.status)" class="h-1.5 w-1.5 rounded-full" />
                                        {{ statusLabel(table.status) }}
                                    </span>
                                </td>
                                <!-- Active -->
                                <td class="hidden px-3 py-3 text-center md:table-cell sm:px-4">
                                    <span
                                        :class="table.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800'
                                            : 'bg-gray-50 text-gray-600 ring-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-700'"
                                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    >
                                        <span
                                            :class="table.is_active ? 'bg-emerald-500' : 'bg-gray-400'"
                                            class="h-1.5 w-1.5 rounded-full"
                                        />
                                        {{ table.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <!-- Actions -->
                                <td class="px-3 py-3 text-right sm:px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button v-if="can('tables.edit')" variant="ghost" size="icon" as-child>
                                            <Link :href="tenantUrl(`tables/${table.id}/edit`)">
                                                <Edit class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button v-if="can('tables.delete')" variant="ghost" size="icon" @click="confirmDelete(table)">
                                            <Trash2 class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    <Pagination :data="tables" />
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Table</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ tableToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteTable">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
