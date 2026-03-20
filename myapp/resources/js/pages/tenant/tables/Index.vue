<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Search, Trash2 } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
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

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const deleteDialog = ref(false);
const tableToDelete = ref<Table | null>(null);

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

const statusColors: Record<string, string> = {
    available: 'default',
    occupied: 'destructive',
    reserved: 'secondary',
    maintenance: 'outline',
};

const breadcrumbs = [{ title: 'Tables', href: tenantUrl('tables') }];
</script>

<template>
    <TenantLayout title="Tables" :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Tables</h1>
                <Button v-if="can('tables.create')" as-child>
                    <Link :href="tenantUrl('tables/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Table
                    </Link>
                </Button>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search tables..." class="pl-9" />
                </div>
                <Select :model-value="statusFilter || 'all'" @update:model-value="onStatusChange">
                    <SelectTrigger class="w-[160px]">
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

            <div class="rounded-md border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Branch</th>
                            <th class="px-4 py-3 text-center font-medium">Capacity</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 text-center font-medium">Active</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="table in tables.data" :key="table.id" class="border-b">
                            <td class="px-4 py-3 font-medium">{{ table.name }}</td>
                            <td class="px-4 py-3">{{ table.branch?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">{{ table.capacity }}</td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="(statusColors[table.status] as any) ?? 'default'">
                                    {{ table.status.charAt(0).toUpperCase() + table.status.slice(1) }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="table.is_active ? 'default' : 'secondary'">
                                    {{ table.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
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
                        <tr v-if="tables.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">No tables found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :data="tables" />
        </div>

        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Table</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ tableToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteTable">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
