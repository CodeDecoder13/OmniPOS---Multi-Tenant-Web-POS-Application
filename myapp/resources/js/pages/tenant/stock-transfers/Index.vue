<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeftRight, CheckCircle, Clock, Eye, Plus, Search, Trash2, Truck } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Dialog,
    DialogScrollContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Pagination from '@/components/Pagination.vue';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import type { StockTransfer, Branch, Product, PaginatedData } from '@/types';

const props = defineProps<{
    transfers: PaginatedData<StockTransfer>;
    branches: Pick<Branch, 'id' | 'name'>[];
    products: Pick<Product, 'id' | 'name' | 'sku'>[];
    filters: { search?: string; status?: string; branch_id?: string };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

// Stats
const stats = computed(() => {
    const data = props.transfers.data;
    return {
        total: data.length,
        pending: data.filter(t => t.status === 'pending').length,
        inTransit: data.filter(t => t.status === 'in_transit').length,
        completed: data.filter(t => t.status === 'completed').length,
    };
});

// Filters
const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const branchFilter = ref(props.filters.branch_id ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters({ search: value || undefined }), 300);
});

function applyFilters(overrides: Record<string, unknown> = {}) {
    router.get(tenantUrl('stock-transfers'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        branch_id: branchFilter.value || undefined,
        ...overrides,
    }, { preserveState: true, replace: true });
}

function onStatusChange(value: string | number | boolean | Record<string, string>) {
    const v = String(value);
    statusFilter.value = v === 'all' ? '' : v;
    applyFilters({ status: v === 'all' ? undefined : v });
}

function onBranchChange(value: string | number | boolean | Record<string, string>) {
    const v = String(value);
    branchFilter.value = v === 'all' ? '' : v;
    applyFilters({ branch_id: v === 'all' ? undefined : v });
}

// Status helpers
function statusLabel(status: string) {
    const map: Record<string, string> = { pending: 'Pending', in_transit: 'In Transit', completed: 'Completed', cancelled: 'Cancelled' };
    return map[status] ?? status;
}

function statusBadgeClasses(status: string) {
    const map: Record<string, string> = {
        pending: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800',
        in_transit: 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800',
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800',
        cancelled: 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800',
    };
    return map[status] ?? 'bg-gray-50 text-gray-600 ring-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-700';
}

function statusDotClass(status: string) {
    const map: Record<string, string> = {
        pending: 'bg-amber-500',
        in_transit: 'bg-blue-500',
        completed: 'bg-emerald-500',
        cancelled: 'bg-red-500',
    };
    return map[status] ?? 'bg-gray-400';
}

// Create Transfer Dialog
const createDialog = ref(false);

const form = useForm({
    source_branch_id: '',
    destination_branch_id: '',
    notes: '',
    items: [{ product_id: '', quantity_requested: 1 }] as { product_id: string; quantity_requested: number }[],
});

function openCreateDialog() {
    form.reset();
    form.clearErrors();
    form.items = [{ product_id: '', quantity_requested: 1 }];
    createDialog.value = true;
}

function addItem() {
    form.items.push({ product_id: '', quantity_requested: 1 });
}

function removeItem(index: number) {
    form.items.splice(index, 1);
}

function submitCreate() {
    form.post(tenantUrl('stock-transfers'), {
        onSuccess: () => { createDialog.value = false; },
    });
}

const breadcrumbs = [{ title: 'Stock Transfers', href: tenantUrl('stock-transfers') }];
</script>

<template>
    <TenantLayout title="Stock Transfers" :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-md">
                        <ArrowLeftRight class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Stock Transfers</h1>
                        <p class="text-sm text-muted-foreground">Transfer inventory between branches</p>
                    </div>
                </div>
                <Button v-if="can('inventory.manage')" class="w-full sm:w-auto" @click="openCreateDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    New Transfer
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                        <ArrowLeftRight class="h-4.5 w-4.5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground">Total Transfers</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/40">
                        <Clock class="h-4.5 w-4.5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.pending }}</p>
                        <p class="text-xs text-muted-foreground">Pending</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/40">
                        <Truck class="h-4.5 w-4.5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.inTransit }}</p>
                        <p class="text-xs text-muted-foreground">In Transit</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                        <CheckCircle class="h-4.5 w-4.5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.completed }}</p>
                        <p class="text-xs text-muted-foreground">Completed</p>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-xl border bg-card p-3 sm:p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1 min-w-0">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search by transfer #..." class="pl-9" />
                    </div>
                    <Select :model-value="statusFilter || 'all'" @update:model-value="onStatusChange">
                        <SelectTrigger class="w-full sm:w-[160px]">
                            <SelectValue placeholder="All Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Status</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="in_transit">In Transit</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select :model-value="branchFilter || 'all'" @update:model-value="onBranchChange">
                        <SelectTrigger class="w-full sm:w-[180px]">
                            <SelectValue placeholder="All Branches" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Branches</SelectItem>
                            <SelectItem v-for="branch in branches" :key="branch.id" :value="String(branch.id)">
                                {{ branch.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="transfers.data.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border bg-card py-16"
            >
                <div class="rounded-full bg-muted p-4">
                    <ArrowLeftRight class="h-8 w-8 text-muted-foreground" />
                </div>
                <h3 class="mt-4 text-lg font-semibold">No stock transfers found</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ search || statusFilter || branchFilter ? 'Try adjusting your filters.' : 'Create your first transfer to get started.' }}
                </p>
                <Button v-if="can('inventory.manage') && !search && !statusFilter && !branchFilter" class="mt-4" @click="openCreateDialog">
                    New Transfer
                </Button>
            </div>

            <!-- Table -->
            <div v-else>
                <div class="overflow-x-auto rounded-xl border bg-card">
                    <table class="w-full min-w-[650px] text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-3 py-3 text-left font-medium sm:px-4">Transfer #</th>
                                <th class="px-3 py-3 text-left font-medium sm:px-4">From</th>
                                <th class="px-3 py-3 text-left font-medium sm:px-4">To</th>
                                <th class="px-3 py-3 text-center font-medium sm:px-4">Status</th>
                                <th class="hidden px-3 py-3 text-left font-medium sm:table-cell sm:px-4">Created By</th>
                                <th class="hidden px-3 py-3 text-left font-medium sm:table-cell sm:px-4">Date</th>
                                <th class="px-3 py-3 text-right font-medium sm:px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="transfer in transfers.data"
                                :key="transfer.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-3 py-3 sm:px-4">
                                    <p class="font-mono text-xs font-medium">{{ transfer.transfer_number }}</p>
                                    <p class="text-xs text-muted-foreground sm:hidden">
                                        {{ new Date(transfer.created_at).toLocaleDateString() }}
                                    </p>
                                </td>
                                <td class="px-3 py-3 sm:px-4">{{ transfer.source_branch?.name ?? '—' }}</td>
                                <td class="px-3 py-3 sm:px-4">{{ transfer.destination_branch?.name ?? '—' }}</td>
                                <td class="px-3 py-3 text-center sm:px-4">
                                    <span
                                        :class="statusBadgeClasses(transfer.status)"
                                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    >
                                        <span :class="statusDotClass(transfer.status)" class="h-1.5 w-1.5 rounded-full" />
                                        {{ statusLabel(transfer.status) }}
                                    </span>
                                </td>
                                <td class="hidden px-3 py-3 sm:table-cell sm:px-4">{{ transfer.creator?.name ?? '—' }}</td>
                                <td class="hidden px-3 py-3 sm:table-cell sm:px-4">{{ new Date(transfer.created_at).toLocaleDateString() }}</td>
                                <td class="px-3 py-3 text-right sm:px-4">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="tenantUrl(`stock-transfers/${transfer.id}`)">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    <Pagination :data="transfers" />
                </div>
            </div>
        </div>

        <!-- Create Transfer Dialog -->
        <Dialog v-model:open="createDialog">
            <DialogScrollContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Create Stock Transfer</DialogTitle>
                    <DialogDescription>
                        Transfer inventory items between branches.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="flex flex-col gap-4 py-2">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Source Branch *</Label>
                            <Select v-model="form.source_branch_id">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.source_branch_id }">
                                    <SelectValue placeholder="Select source branch" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="branch in branches" :key="branch.id" :value="String(branch.id)">
                                        {{ branch.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.source_branch_id" class="text-sm text-destructive">{{ form.errors.source_branch_id }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label>Destination Branch *</Label>
                            <Select v-model="form.destination_branch_id">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.destination_branch_id }">
                                    <SelectValue placeholder="Select destination branch" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="branch in branches" :key="branch.id" :value="String(branch.id)">
                                        {{ branch.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.destination_branch_id" class="text-sm text-destructive">{{ form.errors.destination_branch_id }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Notes</Label>
                        <Textarea v-model="form.notes" rows="2" />
                    </div>

                    <!-- Items -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <Label class="text-base font-semibold">Items</Label>
                            <Button type="button" variant="outline" size="sm" @click="addItem">
                                <Plus class="mr-1 h-4 w-4" /> Add Item
                            </Button>
                        </div>
                        <p v-if="form.errors.items" class="text-sm text-destructive">{{ form.errors.items }}</p>

                        <div v-for="(item, index) in form.items" :key="index" class="flex items-start gap-3 rounded-lg border bg-muted/30 p-3">
                            <div class="flex-1 space-y-2">
                                <Label class="text-xs text-muted-foreground">Product</Label>
                                <Select v-model="item.product_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select product" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="product in products" :key="product.id" :value="String(product.id)">
                                            {{ product.name }} {{ product.sku ? `(${product.sku})` : '' }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="w-24 space-y-2 sm:w-32">
                                <Label class="text-xs text-muted-foreground">Quantity</Label>
                                <Input v-model="item.quantity_requested" type="number" min="1" />
                            </div>
                            <Button type="button" variant="ghost" size="icon" class="mt-6 shrink-0" @click="removeItem(index)" :disabled="form.items.length <= 1">
                                <Trash2 class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button variant="outline" type="button" @click="createDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Creating...' : 'Create Transfer' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>
    </TenantLayout>
</template>
