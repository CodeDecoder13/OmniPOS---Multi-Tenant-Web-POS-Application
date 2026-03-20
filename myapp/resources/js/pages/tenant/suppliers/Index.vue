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
import type { Supplier, PaginatedData } from '@/types';

const props = defineProps<{
    suppliers: PaginatedData<Supplier>;
    filters: { search?: string; is_active?: string };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.is_active ?? '');
const deleteDialog = ref(false);
const supplierToDelete = ref<Supplier | null>(null);

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
    applyFilters({ is_active: v === 'all' ? undefined : v });
}

function applyFilters(overrides: Record<string, unknown> = {}) {
    router.get(tenantUrl('suppliers'), {
        search: search.value || undefined,
        is_active: statusFilter.value === 'all' ? undefined : statusFilter.value || undefined,
        ...overrides,
    }, { preserveState: true, replace: true });
}

function confirmDelete(supplier: Supplier) {
    supplierToDelete.value = supplier;
    deleteDialog.value = true;
}

function deleteSupplier() {
    if (!supplierToDelete.value) return;
    router.delete(tenantUrl(`suppliers/${supplierToDelete.value.id}`), {
        onFinish: () => {
            deleteDialog.value = false;
            supplierToDelete.value = null;
        },
    });
}

const breadcrumbs = [{ title: 'Suppliers', href: tenantUrl('suppliers') }];
</script>

<template>
    <TenantLayout title="Suppliers" :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Suppliers</h1>
                <Button v-if="can('suppliers.create')" as-child>
                    <Link :href="tenantUrl('suppliers/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Supplier
                    </Link>
                </Button>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search suppliers..." class="pl-9" />
                </div>
                <Select :model-value="statusFilter || 'all'" @update:model-value="onStatusChange">
                    <SelectTrigger class="w-[160px]">
                        <SelectValue placeholder="All Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="1">Active</SelectItem>
                        <SelectItem value="0">Inactive</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="rounded-md border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Contact</th>
                            <th class="px-4 py-3 text-left font-medium">Email</th>
                            <th class="px-4 py-3 text-left font-medium">Phone</th>
                            <th class="px-4 py-3 text-center font-medium">Products</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="supplier in suppliers.data" :key="supplier.id" class="border-b">
                            <td class="px-4 py-3 font-medium">{{ supplier.name }}</td>
                            <td class="px-4 py-3">{{ supplier.contact_person ?? '—' }}</td>
                            <td class="px-4 py-3">{{ supplier.email ?? '—' }}</td>
                            <td class="px-4 py-3">{{ supplier.phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">{{ supplier.products_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="supplier.is_active ? 'default' : 'secondary'">
                                    {{ supplier.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button v-if="can('suppliers.edit')" variant="ghost" size="icon" as-child>
                                        <Link :href="tenantUrl(`suppliers/${supplier.id}/edit`)">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button v-if="can('suppliers.delete')" variant="ghost" size="icon" @click="confirmDelete(supplier)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="suppliers.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No suppliers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :data="suppliers" />
        </div>

        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Supplier</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ supplierToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteSupplier">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
