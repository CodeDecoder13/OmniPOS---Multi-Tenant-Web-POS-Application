<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { CheckCircle, Edit, Package, Plus, Search, Trash2, Truck, XCircle } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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

// Stats
const stats = computed(() => {
    const data = props.suppliers.data;
    const active = data.filter(s => s.is_active).length;
    const totalProducts = data.reduce((sum, s) => sum + (s.products_count ?? 0), 0);
    return { total: data.length, active, inactive: data.length - active, totalProducts };
});

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

// Create Supplier Dialog
const createDialog = ref(false);

const form = useForm({
    name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    notes: '',
    is_active: true,
});

function openCreateDialog() {
    form.reset();
    form.clearErrors();
    createDialog.value = true;
}

function submitCreate() {
    form.post(tenantUrl('suppliers'), {
        onSuccess: () => { createDialog.value = false; },
    });
}

const breadcrumbs = [{ title: 'Suppliers', href: tenantUrl('suppliers') }];
</script>

<template>
    <TenantLayout title="Suppliers" :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-md">
                        <Truck class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Suppliers</h1>
                        <p class="text-sm text-muted-foreground">Manage your suppliers and vendors</p>
                    </div>
                </div>
                <Button v-if="can('suppliers.create')" class="w-full sm:w-auto" @click="openCreateDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Supplier
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                        <Truck class="h-4.5 w-4.5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground">Total Suppliers</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                        <CheckCircle class="h-4.5 w-4.5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.active }}</p>
                        <p class="text-xs text-muted-foreground">Active</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-900/40">
                        <XCircle class="h-4.5 w-4.5 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.inactive }}</p>
                        <p class="text-xs text-muted-foreground">Inactive</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/40">
                        <Package class="h-4.5 w-4.5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.totalProducts }}</p>
                        <p class="text-xs text-muted-foreground">Total Products</p>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-xl border bg-card p-3 sm:p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative w-full max-w-sm">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search suppliers..." class="pl-9" />
                    </div>
                    <Select :model-value="statusFilter || 'all'" @update:model-value="onStatusChange">
                        <SelectTrigger class="w-full sm:w-[160px]">
                            <SelectValue placeholder="All Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Status</SelectItem>
                            <SelectItem value="1">Active</SelectItem>
                            <SelectItem value="0">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[600px] text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-3 py-3 text-left font-medium sm:px-4">Name</th>
                            <th class="hidden px-3 py-3 text-left font-medium sm:table-cell sm:px-4">Contact</th>
                            <th class="hidden px-3 py-3 text-left font-medium sm:table-cell sm:px-4">Email</th>
                            <th class="hidden px-3 py-3 text-left font-medium sm:table-cell sm:px-4">Phone</th>
                            <th class="px-3 py-3 text-center font-medium sm:px-4">Products</th>
                            <th class="px-3 py-3 text-center font-medium sm:px-4">Status</th>
                            <th class="px-3 py-3 text-right font-medium sm:px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="supplier in suppliers.data" :key="supplier.id" class="border-b transition-colors hover:bg-muted/30">
                            <td class="px-3 py-3 sm:px-4">
                                <div class="font-medium">{{ supplier.name }}</div>
                                <div class="mt-0.5 space-y-0.5 text-xs text-muted-foreground sm:hidden">
                                    <div v-if="supplier.contact_person">{{ supplier.contact_person }}</div>
                                    <div v-if="supplier.email">{{ supplier.email }}</div>
                                    <div v-if="supplier.phone">{{ supplier.phone }}</div>
                                </div>
                            </td>
                            <td class="hidden px-3 py-3 sm:table-cell sm:px-4">{{ supplier.contact_person ?? '—' }}</td>
                            <td class="hidden px-3 py-3 sm:table-cell sm:px-4">{{ supplier.email ?? '—' }}</td>
                            <td class="hidden px-3 py-3 sm:table-cell sm:px-4">{{ supplier.phone ?? '—' }}</td>
                            <td class="px-3 py-3 text-center tabular-nums sm:px-4">{{ supplier.products_count ?? 0 }}</td>
                            <td class="px-3 py-3 text-center sm:px-4">
                                <span
                                    v-if="supplier.is_active"
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                    Active
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-700"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400" />
                                    Inactive
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right sm:px-4">
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
                            <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">
                                <Truck class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                No suppliers found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :data="suppliers" />
        </div>

        <!-- Create Supplier Dialog -->
        <Dialog v-model:open="createDialog">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Create Supplier</DialogTitle>
                    <DialogDescription>
                        Fill in the details below to add a new supplier.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="flex flex-col gap-4 py-2">
                    <div class="space-y-2">
                        <Label for="create-name">Name *</Label>
                        <Input id="create-name" v-model="form.name" :class="{ 'border-destructive': form.errors.name }" />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="create-contact">Contact Person</Label>
                            <Input id="create-contact" v-model="form.contact_person" />
                            <p v-if="form.errors.contact_person" class="text-sm text-destructive">{{ form.errors.contact_person }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="create-email">Email</Label>
                            <Input id="create-email" v-model="form.email" type="email" />
                            <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-phone">Phone</Label>
                        <Input id="create-phone" v-model="form.phone" />
                        <p v-if="form.errors.phone" class="text-sm text-destructive">{{ form.errors.phone }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-address">Address</Label>
                        <Textarea id="create-address" v-model="form.address" rows="2" />
                        <p v-if="form.errors.address" class="text-sm text-destructive">{{ form.errors.address }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-notes">Notes</Label>
                        <Textarea id="create-notes" v-model="form.notes" rows="2" />
                        <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Switch id="create-active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                        <Label for="create-active">Active</Label>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button variant="outline" type="button" @click="createDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Creating...' : 'Create Supplier' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Supplier</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ supplierToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteSupplier">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
