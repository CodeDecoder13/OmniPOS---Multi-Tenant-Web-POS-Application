<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { CheckCircle, ClipboardList, Eye, FileEdit, Plus, Search, Send, Trash2 } from 'lucide-vue-next';
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
import { useCurrency } from '@/composables/useCurrency';
import type { PurchaseOrder, Supplier, Branch, Product, PaginatedData } from '@/types';

const props = defineProps<{
    purchaseOrders: PaginatedData<PurchaseOrder>;
    suppliers: Pick<Supplier, 'id' | 'name'>[];
    branches: Pick<Branch, 'id' | 'name'>[];
    products: Pick<Product, 'id' | 'name' | 'sku' | 'cost_price'>[];
    filters: { search?: string; status?: string; supplier_id?: string; branch_id?: string };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const supplierFilter = ref(props.filters.supplier_id ?? '');
const branchFilter = ref(props.filters.branch_id ?? '');

const stats = computed(() => {
    const data = props.purchaseOrders.data;
    return {
        total: data.length,
        draft: data.filter(po => po.status === 'draft').length,
        sentPartial: data.filter(po => po.status === 'sent' || po.status === 'partial').length,
        received: data.filter(po => po.status === 'received').length,
    };
});

let debounceTimer: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters({ search: value || undefined }), 300);
});

function applyFilters(overrides: Record<string, unknown> = {}) {
    router.get(tenantUrl('purchase-orders'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        supplier_id: supplierFilter.value || undefined,
        branch_id: branchFilter.value || undefined,
        ...overrides,
    }, { preserveState: true, replace: true });
}

function onFilterChange(key: string, value: string | number | boolean | Record<string, string>) {
    const val = String(value) === 'all' ? '' : String(value);
    if (key === 'status') statusFilter.value = val;
    if (key === 'supplier_id') supplierFilter.value = val;
    if (key === 'branch_id') branchFilter.value = val;
    applyFilters({ [key]: val || undefined });
}

function statusDotClass(status: string) {
    const map: Record<string, string> = {
        draft: 'bg-gray-50 text-gray-600 ring-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-700',
        sent: 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800',
        partial: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800',
        received: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800',
        cancelled: 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800',
    };
    return map[status] ?? map.draft;
}

function statusDot(status: string) {
    const map: Record<string, string> = {
        draft: 'bg-gray-400',
        sent: 'bg-blue-500',
        partial: 'bg-amber-500',
        received: 'bg-emerald-500',
        cancelled: 'bg-red-500',
    };
    return map[status] ?? 'bg-gray-400';
}

function statusLabel(status: string) {
    const map: Record<string, string> = { draft: 'Draft', sent: 'Sent', partial: 'Partial', received: 'Received', cancelled: 'Cancelled' };
    return map[status] ?? status;
}

// Create PO Dialog
const createDialog = ref(false);

const form = useForm({
    supplier_id: '',
    branch_id: '',
    expected_date: '',
    notes: '',
    items: [{ product_id: '', quantity_ordered: 1, unit_cost: '' }] as { product_id: string; quantity_ordered: number; unit_cost: string }[],
});

function openCreateDialog() {
    form.reset();
    form.clearErrors();
    form.items = [{ product_id: '', quantity_ordered: 1, unit_cost: '' }];
    createDialog.value = true;
}

function addItem() {
    form.items.push({ product_id: '', quantity_ordered: 1, unit_cost: '' });
}

function removeItem(index: number) {
    form.items.splice(index, 1);
}

function onProductSelect(index: number, productId: string | number | boolean | Record<string, string>) {
    const id = String(productId);
    form.items[index].product_id = id;
    const product = props.products.find(p => String(p.id) === id);
    if (product?.cost_price) {
        form.items[index].unit_cost = product.cost_price;
    }
}

const totalAmount = computed(() =>
    form.items.reduce((sum, item) => sum + (Number(item.unit_cost) || 0) * (item.quantity_ordered || 0), 0)
);

function submitCreate() {
    form.post(tenantUrl('purchase-orders'), {
        onSuccess: () => { createDialog.value = false; },
    });
}

const breadcrumbs = [{ title: 'Purchase Orders', href: tenantUrl('purchase-orders') }];
</script>

<template>
    <TenantLayout title="Purchase Orders" :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 text-white shadow-md">
                        <ClipboardList class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Purchase Orders</h1>
                        <p class="text-sm text-muted-foreground">Create and track supplier orders</p>
                    </div>
                </div>
                <Button v-if="can('inventory.manage')" class="w-full sm:w-auto" @click="openCreateDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    New PO
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                        <ClipboardList class="h-4.5 w-4.5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground">Total POs</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/40">
                        <FileEdit class="h-4.5 w-4.5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.draft }}</p>
                        <p class="text-xs text-muted-foreground">Draft</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/40">
                        <Send class="h-4.5 w-4.5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.sentPartial }}</p>
                        <p class="text-xs text-muted-foreground">Sent / Partial</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
                        <CheckCircle class="h-4.5 w-4.5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.received }}</p>
                        <p class="text-xs text-muted-foreground">Received</p>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-xl border bg-card p-3 sm:p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    <div class="relative w-full sm:max-w-sm">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search PO #..." class="pl-9" />
                    </div>
                    <Select :model-value="statusFilter || 'all'" @update:model-value="v => onFilterChange('status', v)">
                        <SelectTrigger class="w-full sm:w-[140px]"><SelectValue placeholder="Status" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Status</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="sent">Sent</SelectItem>
                            <SelectItem value="partial">Partial</SelectItem>
                            <SelectItem value="received">Received</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select :model-value="supplierFilter || 'all'" @update:model-value="v => onFilterChange('supplier_id', v)">
                        <SelectTrigger class="w-full sm:w-[160px]"><SelectValue placeholder="Supplier" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Suppliers</SelectItem>
                            <SelectItem v-for="s in suppliers" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select :model-value="branchFilter || 'all'" @update:model-value="v => onFilterChange('branch_id', v)">
                        <SelectTrigger class="w-full sm:w-[160px]"><SelectValue placeholder="Branch" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Branches</SelectItem>
                            <SelectItem v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl border bg-card">
                <table class="min-w-[650px] w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">PO #</th>
                            <th class="px-4 py-3 text-left font-medium">Supplier</th>
                            <th class="hidden px-4 py-3 text-left font-medium sm:table-cell">Branch</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="hidden px-4 py-3 text-left font-medium sm:table-cell">Date</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="po in purchaseOrders.data" :key="po.id" class="border-b transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-mono text-xs font-medium">{{ po.po_number }}</td>
                            <td class="px-4 py-3">
                                <div>{{ po.supplier?.name ?? '—' }}</div>
                                <div class="text-xs text-muted-foreground sm:hidden">{{ po.branch?.name ?? '—' }}</div>
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell">{{ po.branch?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ formatCurrency(po.total_amount) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="statusDotClass(po.status)"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(po.status)" />
                                    {{ statusLabel(po.status) }}
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell">{{ new Date(po.created_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="tenantUrl(`purchase-orders/${po.id}`)">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="purchaseOrders.data.length === 0">
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                                        <ClipboardList class="h-6 w-6 text-muted-foreground" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-muted-foreground">No purchase orders found</p>
                                        <p class="text-sm text-muted-foreground/70">Try adjusting your search or filters.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :data="purchaseOrders" />
        </div>

        <!-- Create PO Dialog -->
        <Dialog v-model:open="createDialog">
            <DialogScrollContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Create Purchase Order</DialogTitle>
                    <DialogDescription>
                        Fill in the details below to create a new purchase order.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="flex flex-col gap-4 py-2">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Supplier *</Label>
                            <Select v-model="form.supplier_id">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.supplier_id }">
                                    <SelectValue placeholder="Select supplier" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in suppliers" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.supplier_id" class="text-sm text-destructive">{{ form.errors.supplier_id }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label>Receiving Branch *</Label>
                            <Select v-model="form.branch_id">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.branch_id }">
                                    <SelectValue placeholder="Select branch" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.branch_id" class="text-sm text-destructive">{{ form.errors.branch_id }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Expected Date</Label>
                            <Input v-model="form.expected_date" type="date" />
                        </div>
                        <div class="space-y-2">
                            <Label>Notes</Label>
                            <Textarea v-model="form.notes" rows="1" />
                        </div>
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
                                <Select :model-value="item.product_id" @update:model-value="v => onProductSelect(index, v)">
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
                            <div class="w-20 space-y-2 sm:w-24">
                                <Label class="text-xs text-muted-foreground">Qty</Label>
                                <Input v-model="item.quantity_ordered" type="number" min="1" />
                            </div>
                            <div class="w-24 space-y-2 sm:w-32">
                                <Label class="text-xs text-muted-foreground">Unit Cost</Label>
                                <Input v-model="item.unit_cost" type="number" step="0.01" min="0" />
                            </div>
                            <Button type="button" variant="ghost" size="icon" class="mt-6 shrink-0" @click="removeItem(index)" :disabled="form.items.length <= 1">
                                <Trash2 class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>

                        <div class="text-right text-sm font-semibold">
                            Total: {{ formatCurrency(totalAmount) }}
                        </div>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button variant="outline" type="button" @click="createDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Creating...' : 'Create PO' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>
    </TenantLayout>
</template>
