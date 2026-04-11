<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { BarChart3, CheckCircle, Pencil, Plus, Search, Tag, Trash2, XCircle } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogDescription, DialogFooter, DialogHeader, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import Pagination from '@/components/Pagination.vue';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import { useCurrency } from '@/composables/useCurrency';
import type { Promotion, PaginatedData } from '@/types';

const props = defineProps<{
    promotions: PaginatedData<Promotion>;
    filters: { search?: string; is_active?: string };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();
const { formatCurrency } = useCurrency();

// Search & Filters
const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.is_active ?? '');

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
    router.get(tenantUrl('promotions'), {
        search: search.value || undefined,
        is_active: statusFilter.value === 'all' ? undefined : statusFilter.value || undefined,
        ...overrides,
    }, { preserveState: true, replace: true });
}

// Stats
const stats = computed(() => {
    const data = props.promotions.data;
    const active = data.filter(p => p.is_active).length;
    const totalUsed = data.reduce((sum, p) => sum + p.used_count, 0);
    return { total: data.length, active, inactive: data.length - active, totalUsed };
});

// Create/Edit Dialog
const formDialog = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const editingPreset = ref(false);

const form = useForm({
    code: '',
    name: '',
    type: 'percentage',
    value: 0 as number,
    min_order_amount: undefined as number | undefined,
    max_discount: undefined as number | undefined,
    start_date: '',
    end_date: '',
    usage_limit: undefined as number | undefined,
    description: '',
    is_active: true,
});

function openCreateDialog() {
    isEditing.value = false;
    editingId.value = null;
    editingPreset.value = false;
    form.reset();
    form.clearErrors();
    formDialog.value = true;
}

function openEditDialog(promo: Promotion) {
    isEditing.value = true;
    editingId.value = promo.id;
    editingPreset.value = promo.is_preset;
    form.code = promo.code;
    form.name = promo.name;
    form.type = promo.type;
    form.value = Number(promo.value);
    form.min_order_amount = promo.min_order_amount ? Number(promo.min_order_amount) : undefined;
    form.max_discount = promo.max_discount ? Number(promo.max_discount) : undefined;
    form.start_date = promo.start_date ?? '';
    form.end_date = promo.end_date ?? '';
    form.usage_limit = promo.usage_limit ?? undefined;
    form.description = promo.description ?? '';
    form.is_active = promo.is_active;
    form.clearErrors();
    formDialog.value = true;
}

function onTypeChange(value: string | number | boolean | Record<string, string>) {
    form.type = String(value);
}

function submitForm() {
    const submit = form.transform((data) => ({
        ...data,
        code: data.code.toUpperCase(),
        start_date: data.start_date || null,
        end_date: data.end_date || null,
        min_order_amount: data.min_order_amount || null,
        max_discount: data.max_discount || null,
        usage_limit: data.usage_limit || null,
        description: data.description || null,
    }));

    if (isEditing.value && editingId.value) {
        submit.put(tenantUrl(`promotions/${editingId.value}`), {
            onSuccess: () => { formDialog.value = false; },
        });
    } else {
        submit.post(tenantUrl('promotions'), {
            onSuccess: () => { formDialog.value = false; },
        });
    }
}

// Delete Dialog
const deleteDialog = ref(false);
const promoToDelete = ref<Promotion | null>(null);

function confirmDelete(promo: Promotion) {
    promoToDelete.value = promo;
    deleteDialog.value = true;
}

function deletePromo() {
    if (!promoToDelete.value) return;
    router.delete(tenantUrl(`promotions/${promoToDelete.value.id}`), {
        onFinish: () => {
            deleteDialog.value = false;
            promoToDelete.value = null;
        },
    });
}

// Helpers
function formatType(type: string) {
    const map: Record<string, string> = {
        percentage: 'Percent',
        fixed: 'Fixed',
        buy_x_get_y: 'Buy X Get Y',
        student: 'Student',
        pwd: 'PWD',
        senior_citizen: 'Senior',
    };
    return map[type] ?? type;
}

function typeBadgeClasses(type: string) {
    const map: Record<string, string> = {
        percentage: 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800',
        fixed: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800',
        buy_x_get_y: 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:ring-purple-800',
        student: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800',
        pwd: 'bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-900/30 dark:text-teal-300 dark:ring-teal-800',
        senior_citizen: 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:ring-rose-800',
    };
    return map[type] ?? 'bg-gray-50 text-gray-700 ring-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:ring-gray-800';
}

function formatValue(promo: Promotion) {
    if (promo.type === 'percentage' || promo.type === 'student' || promo.type === 'pwd' || promo.type === 'senior_citizen') return `${promo.value}%`;
    return formatCurrency(promo.value);
}

function formatDate(date: string | null) {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', { dateStyle: 'medium' });
}

const breadcrumbs = [{ title: 'Promotions', href: tenantUrl('promotions') }];
</script>

<template>
    <TenantLayout title="Promotions" :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white shadow-md">
                        <Tag class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Promotions</h1>
                        <p class="text-sm text-muted-foreground">Manage discount codes and special offers</p>
                    </div>
                </div>
                <Button v-if="can('promotions.create')" class="w-full sm:w-auto" @click="openCreateDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Promotion
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                        <Tag class="h-4.5 w-4.5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground">Total Promotions</p>
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
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/40">
                        <BarChart3 class="h-4.5 w-4.5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.totalUsed }}</p>
                        <p class="text-xs text-muted-foreground">Total Used</p>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-xl border bg-card p-3 sm:p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative w-full sm:max-w-sm">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search by code or name..." class="pl-9" />
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
                            <th class="px-3 py-3 text-left font-medium sm:px-4">Code</th>
                            <th class="px-3 py-3 text-left font-medium sm:px-4">Name</th>
                            <th class="hidden px-3 py-3 text-center font-medium sm:table-cell sm:px-4">Type</th>
                            <th class="px-3 py-3 text-center font-medium sm:px-4">Value</th>
                            <th class="hidden px-3 py-3 text-center font-medium md:table-cell md:px-4">Dates</th>
                            <th class="hidden px-3 py-3 text-center font-medium md:table-cell md:px-4">Usage</th>
                            <th class="hidden px-3 py-3 text-center font-medium sm:table-cell sm:px-4">Status</th>
                            <th class="px-3 py-3 text-right font-medium sm:px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="promo in promotions.data" :key="promo.id" class="border-b transition-colors hover:bg-muted/30">
                            <td class="px-3 py-3 sm:px-4">
                                <span class="font-mono font-medium">{{ promo.code }}</span>
                                <!-- Mobile: show type badge under code -->
                                <span class="mt-1 block sm:hidden">
                                    <span :class="['inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset', typeBadgeClasses(promo.type)]">
                                        {{ formatType(promo.type) }}
                                    </span>
                                </span>
                            </td>
                            <td class="px-3 py-3 sm:px-4">
                                <span>{{ promo.name }}</span>
                                <!-- Mobile: show status under name -->
                                <span class="mt-1 block sm:hidden">
                                    <span v-if="promo.is_active" class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                        Active
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400" />
                                        Inactive
                                    </span>
                                </span>
                            </td>
                            <td class="hidden px-3 py-3 text-center sm:table-cell sm:px-4">
                                <span :class="['inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset', typeBadgeClasses(promo.type)]">
                                    {{ formatType(promo.type) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center font-medium tabular-nums sm:px-4">{{ formatValue(promo) }}</td>
                            <td class="hidden px-3 py-3 text-center text-xs text-muted-foreground tabular-nums md:table-cell md:px-4">
                                {{ formatDate(promo.start_date) }} — {{ formatDate(promo.end_date) }}
                            </td>
                            <td class="hidden px-3 py-3 text-center tabular-nums md:table-cell md:px-4">
                                {{ promo.used_count }}{{ promo.usage_limit ? ` / ${promo.usage_limit}` : '' }}
                            </td>
                            <td class="hidden px-3 py-3 text-center sm:table-cell sm:px-4">
                                <span
                                    v-if="promo.is_active"
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
                                    <Button v-if="can('promotions.edit')" variant="ghost" size="icon" @click="openEditDialog(promo)">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button v-if="can('promotions.delete') && !promo.is_preset" variant="ghost" size="icon" @click="confirmDelete(promo)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="promotions.data.length === 0">
                            <td colspan="8" class="px-4 py-12 text-center text-muted-foreground">
                                <Tag class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                No promotions found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :data="promotions" />
        </div>

        <!-- Create/Edit Dialog -->
        <Dialog v-model:open="formDialog">
            <DialogScrollContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit Promotion' : 'Create Promotion' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? 'Update the promotion details below.' : 'Fill in the details to create a new promotion.' }}
                    </DialogDescription>
                </DialogHeader>

                <!-- Preset notice -->
                <div v-if="editingPreset" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                    This is a preset discount. Only the value, description, and active status can be modified.
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Row 1: Code + Name -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="code">Code *</Label>
                            <Input id="code" v-model="form.code" placeholder="e.g. WELCOME20" class="uppercase" :disabled="editingPreset" :class="{ 'border-destructive': form.errors.code }" />
                            <p v-if="form.errors.code" class="text-sm text-destructive">{{ form.errors.code }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="name">Name *</Label>
                            <Input id="name" v-model="form.name" placeholder="e.g. Welcome Discount" :disabled="editingPreset" :class="{ 'border-destructive': form.errors.name }" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <!-- Row 2: Type + Value -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="type">Type *</Label>
                            <Select :model-value="form.type" :disabled="editingPreset" @update:model-value="onTypeChange">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.type }">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="percentage">Percentage (%)</SelectItem>
                                    <SelectItem value="fixed">Fixed Amount</SelectItem>
                                    <SelectItem value="buy_x_get_y">Buy X Get Y</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.type" class="text-sm text-destructive">{{ form.errors.type }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="value">Value *</Label>
                            <Input id="value" v-model.number="form.value" type="number" min="0" :step="form.type === 'percentage' ? 1 : 0.01" :class="{ 'border-destructive': form.errors.value }" />
                            <p v-if="form.errors.value" class="text-sm text-destructive">{{ form.errors.value }}</p>
                        </div>
                    </div>

                    <!-- Row 3: Min Order + Max Discount (hidden for presets) -->
                    <div v-if="!editingPreset" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="min_order_amount">Minimum Order Amount</Label>
                            <Input id="min_order_amount" v-model.number="form.min_order_amount" type="number" min="0" step="0.01" placeholder="No minimum" />
                            <p v-if="form.errors.min_order_amount" class="text-sm text-destructive">{{ form.errors.min_order_amount }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="max_discount">Maximum Discount</Label>
                            <Input id="max_discount" v-model.number="form.max_discount" type="number" min="0" step="0.01" placeholder="No cap" />
                            <p v-if="form.errors.max_discount" class="text-sm text-destructive">{{ form.errors.max_discount }}</p>
                        </div>
                    </div>

                    <!-- Row 4: Start Date + End Date (hidden for presets) -->
                    <div v-if="!editingPreset" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="start_date">Start Date</Label>
                            <Input id="start_date" v-model="form.start_date" type="date" />
                            <p v-if="form.errors.start_date" class="text-sm text-destructive">{{ form.errors.start_date }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date">End Date</Label>
                            <Input id="end_date" v-model="form.end_date" type="date" />
                            <p v-if="form.errors.end_date" class="text-sm text-destructive">{{ form.errors.end_date }}</p>
                        </div>
                    </div>

                    <!-- Row 5: Usage Limit (hidden for presets) -->
                    <div v-if="!editingPreset" class="space-y-2">
                        <Label for="usage_limit">Usage Limit</Label>
                        <Input id="usage_limit" v-model.number="form.usage_limit" type="number" min="1" placeholder="Unlimited" />
                        <p v-if="form.errors.usage_limit" class="text-sm text-destructive">{{ form.errors.usage_limit }}</p>
                    </div>

                    <!-- Row 6: Description -->
                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <Textarea id="description" v-model="form.description" rows="2" placeholder="Optional description..." />
                        <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                    </div>

                    <!-- Row 7: Active switch -->
                    <div class="flex items-center gap-2">
                        <Switch id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                        <Label for="is_active">Active</Label>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button variant="outline" type="button" @click="formDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ isEditing ? 'Update Promotion' : 'Create Promotion' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogScrollContent>
                <DialogHeader>
                    <DialogTitle>Delete Promotion</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ promoToDelete?.code }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deletePromo">Delete</Button>
                </DialogFooter>
            </DialogScrollContent>
        </Dialog>
    </TenantLayout>
</template>
