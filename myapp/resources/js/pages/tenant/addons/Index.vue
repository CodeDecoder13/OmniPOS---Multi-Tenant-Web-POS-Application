<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { CheckCircle, Pencil, Plus, Puzzle, Search, Tag, Trash2, XCircle } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Pagination from '@/components/Pagination.vue';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import type { Addon, PaginatedData } from '@/types';

const props = defineProps<{
    addons: PaginatedData<Addon>;
    filters: { search?: string; is_active?: string };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

// Search
const search = ref(props.filters.search ?? '');
let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(tenantUrl('addons'), { search: value || undefined }, { preserveState: true, replace: true });
    }, 300);
});

// Stats
const stats = computed(() => {
    const data = props.addons.data;
    const active = data.filter(a => a.is_active).length;
    const categories = new Set(data.filter(a => a.category_label).map(a => a.category_label)).size;
    return { total: data.length, active, inactive: data.length - active, categories };
});

// Create/Edit dialog
const formDialog = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    price: '',
    category_label: '',
    is_active: true,
    sort_order: 0,
});

function openCreateDialog() {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    formDialog.value = true;
}

function openEditDialog(addon: Addon) {
    isEditing.value = true;
    editingId.value = addon.id;
    form.name = addon.name;
    form.price = addon.price;
    form.category_label = addon.category_label ?? '';
    form.is_active = addon.is_active;
    form.sort_order = addon.sort_order;
    form.clearErrors();
    formDialog.value = true;
}

function submitForm() {
    if (isEditing.value && editingId.value) {
        form.put(tenantUrl(`addons/${editingId.value}`), {
            onSuccess: () => { formDialog.value = false; },
        });
    } else {
        form.post(tenantUrl('addons'), {
            onSuccess: () => { formDialog.value = false; },
        });
    }
}

// Delete dialog
const deleteDialog = ref(false);
const addonToDelete = ref<Addon | null>(null);

function confirmDelete(addon: Addon) {
    addonToDelete.value = addon;
    deleteDialog.value = true;
}

function deleteAddon() {
    if (!addonToDelete.value) return;
    router.delete(tenantUrl(`addons/${addonToDelete.value.id}`), {
        onFinish: () => {
            deleteDialog.value = false;
            addonToDelete.value = null;
        },
    });
}

const breadcrumbs = [{ title: 'Add-ons', href: tenantUrl('addons') }];
</script>

<template>
    <TenantLayout title="Add-ons" :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-red-600 text-white shadow-md">
                        <Puzzle class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Add-ons</h1>
                        <p class="text-sm text-muted-foreground">Manage product add-ons and extras</p>
                    </div>
                </div>
                <Button v-if="can('products.create')" class="w-full sm:w-auto" @click="openCreateDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Add-on
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                        <Puzzle class="h-4.5 w-4.5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground">Total Add-ons</p>
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
                        <Tag class="h-4.5 w-4.5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.categories }}</p>
                        <p class="text-xs text-muted-foreground">Categories</p>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="rounded-xl border bg-card p-3 sm:p-4">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search add-ons..." class="pl-9" />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[500px] text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-3 py-3 text-left font-medium sm:px-4">Name</th>
                            <th class="hidden px-3 py-3 text-left font-medium sm:table-cell sm:px-4">Category</th>
                            <th class="px-3 py-3 text-right font-medium sm:px-4">Price</th>
                            <th class="hidden px-3 py-3 text-center font-medium sm:table-cell sm:px-4">Order</th>
                            <th class="px-3 py-3 text-center font-medium sm:px-4">Status</th>
                            <th class="px-3 py-3 text-right font-medium sm:px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="addon in addons.data" :key="addon.id" class="border-b transition-colors hover:bg-muted/30">
                            <td class="px-3 py-3 font-medium sm:px-4">{{ addon.name }}</td>
                            <td class="hidden px-3 py-3 sm:table-cell sm:px-4">
                                <span v-if="addon.category_label" class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:ring-purple-800">
                                    {{ addon.category_label }}
                                </span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums sm:px-4">{{ Number(addon.price).toFixed(2) }}</td>
                            <td class="hidden px-3 py-3 text-center tabular-nums sm:table-cell sm:px-4">{{ addon.sort_order }}</td>
                            <td class="px-3 py-3 text-center sm:px-4">
                                <span
                                    v-if="addon.is_active"
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
                                    <Button v-if="can('products.edit')" variant="ghost" size="icon" @click="openEditDialog(addon)">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button v-if="can('products.delete')" variant="ghost" size="icon" @click="confirmDelete(addon)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="addons.data.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                                <Puzzle class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                No add-ons found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :data="addons" />
        </div>

        <!-- Create/Edit Dialog -->
        <Dialog v-model:open="formDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit Add-on' : 'Create Add-on' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? 'Update the add-on details below.' : 'Fill in the details to create a new add-on.' }}
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Name *</Label>
                        <Input id="name" v-model="form.name" :class="{ 'border-destructive': form.errors.name }" />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="price">Price *</Label>
                            <Input id="price" v-model="form.price" type="number" step="0.01" min="0" :class="{ 'border-destructive': form.errors.price }" />
                            <p v-if="form.errors.price" class="text-sm text-destructive">{{ form.errors.price }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="sort_order">Sort Order</Label>
                            <Input id="sort_order" v-model="form.sort_order" type="number" min="0" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="category_label">Category Label</Label>
                        <Input id="category_label" v-model="form.category_label" placeholder="e.g. Toppings, Extras" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Switch id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                        <Label for="is_active">Active</Label>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button variant="outline" type="button" @click="formDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ isEditing ? 'Update Add-on' : 'Create Add-on' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Add-on</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ addonToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteAddon">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
