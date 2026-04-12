<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Barcode, CheckCircle, Edit, Layers, Package, Plus, Search, Trash2, XCircle } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import UpgradePlanModal from '@/components/UpgradePlanModal.vue';
import Pagination from '@/components/Pagination.vue';
import BarcodeLabel from '@/components/BarcodeLabel.vue';
import type { BreadcrumbItem, Category, PaginatedData, Product } from '@/types';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import { usePlanLimits } from '@/composables/usePlanLimits';
import { useCurrency } from '@/composables/useCurrency';

const props = defineProps<{
    products: PaginatedData<Product>;
    categories: Pick<Category, 'id' | 'name'>[];
    filters: {
        search?: string;
        category_id?: string;
        is_active?: string;
    };
    productsCount: number;
}>();

const page = usePage();
const { tenant, tenantUrl } = useTenant();
const { can } = usePermissions();
const { limitReached } = usePlanLimits('products', () => props.productsCount);
const { formatCurrency } = useCurrency();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Products', href: tenantUrl('products') },
];

// Stats
const stats = computed(() => {
    const data = props.products.data;
    const active = data.filter(p => p.is_active).length;
    return {
        total: props.productsCount,
        active,
        inactive: data.length - active,
        categories: props.categories.length,
    };
});

// Filters
const search = ref(props.filters.search ?? '');
const categoryFilter = ref(props.filters.category_id ?? 'all');
const statusFilter = ref(props.filters.is_active ?? 'all');

let searchTimeout: ReturnType<typeof setTimeout>;

function applyFilters() {
    const params: Record<string, string> = {};
    if (search.value) params.search = search.value;
    if (categoryFilter.value && categoryFilter.value !== 'all') params.category_id = categoryFilter.value;
    if (statusFilter.value && statusFilter.value !== 'all') params.is_active = statusFilter.value;

    router.get(tenantUrl('products'), params, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch([categoryFilter, statusFilter], () => {
    applyFilters();
});

// Delete dialog
const deleteDialog = ref(false);
const productToDelete = ref<Product | null>(null);
const deleting = ref(false);

function confirmDelete(product: Product) {
    productToDelete.value = product;
    deleteDialog.value = true;
}

function deleteProduct() {
    if (!productToDelete.value) return;
    deleting.value = true;
    router.delete(tenantUrl(`products/${productToDelete.value.id}`), {
        onFinish: () => {
            deleting.value = false;
            deleteDialog.value = false;
            productToDelete.value = null;
        },
    });
}

// Barcode dialog
const barcodeDialog = ref(false);
const barcodeProduct = ref<Product | null>(null);

function showBarcode(product: Product) {
    barcodeProduct.value = product;
    barcodeDialog.value = true;
}

// Upgrade modal
const upgradeModal = ref(false);
const plans = page.props.plans as any[];
const currentPlanSlug = tenant.value?.subscription?.plan?.slug ?? '';

function handleAddProduct() {
    if (limitReached.value) {
        upgradeModal.value = true;
    } else {
        router.visit(tenantUrl('products/create'));
    }
}
</script>

<template>
    <Head title="Products" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4 sm:gap-6 sm:p-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 text-white shadow-md">
                        <Package class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Products</h1>
                        <p class="text-sm text-muted-foreground">Manage your product catalog</p>
                    </div>
                </div>
                <Button v-if="can('products.create')" class="w-full sm:w-auto" @click="handleAddProduct">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Product
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                        <Package class="h-4.5 w-4.5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground">Total Products</p>
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
                        <Layers class="h-4.5 w-4.5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.categories }}</p>
                        <p class="text-xs text-muted-foreground">Categories</p>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="rounded-xl border bg-card p-3 sm:p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1 min-w-0">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Search by name or SKU..."
                            class="pl-9"
                        />
                    </div>
                    <Select v-model="categoryFilter">
                        <SelectTrigger class="w-full sm:w-[180px]">
                            <SelectValue placeholder="All Categories" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Categories</SelectItem>
                            <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                                {{ cat.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="statusFilter">
                        <SelectTrigger class="w-full sm:w-[140px]">
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

            <!-- Empty State -->
            <div
                v-if="products.data.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border bg-card py-16"
            >
                <div class="rounded-full bg-muted p-4">
                    <Package class="h-8 w-8 text-muted-foreground" />
                </div>
                <h3 class="mt-4 text-lg font-semibold">No products found</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ search || categoryFilter !== 'all' || statusFilter !== 'all' ? 'Try adjusting your filters.' : 'Create your first product to get started.' }}
                </p>
                <Button v-if="can('products.create') && !search && categoryFilter === 'all'" class="mt-4" @click="handleAddProduct">
                    Create Product
                </Button>
            </div>

            <!-- Table -->
            <div v-else>
                <div class="overflow-x-auto rounded-xl border bg-card">
                    <table class="w-full min-w-[600px] text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-3 py-3 text-left font-medium sm:px-4">Product</th>
                                <th class="hidden px-3 py-3 text-left font-medium md:table-cell sm:px-4">SKU</th>
                                <th class="hidden px-3 py-3 text-left font-medium lg:table-cell sm:px-4">Category</th>
                                <th class="px-3 py-3 text-right font-medium sm:px-4">Price</th>
                                <th class="hidden px-3 py-3 text-right font-medium md:table-cell sm:px-4">Cost</th>
                                <th class="px-3 py-3 text-center font-medium sm:px-4">Status</th>
                                <th class="px-3 py-3 text-right font-medium sm:px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/30"
                            >
                                <!-- Product: thumbnail + name -->
                                <td class="px-3 py-3 sm:px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 shrink-0 overflow-hidden rounded-md bg-muted">
                                            <img
                                                v-if="product.image_url"
                                                :src="product.image_url"
                                                :alt="product.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <div v-else class="flex h-full w-full items-center justify-center">
                                                <Package class="h-4 w-4 text-muted-foreground" />
                                            </div>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium">{{ product.name }}</p>
                                            <p class="truncate text-xs text-muted-foreground lg:hidden">
                                                {{ product.category?.name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <!-- SKU -->
                                <td class="hidden px-3 py-3 font-mono text-xs md:table-cell sm:px-4">{{ product.sku || '—' }}</td>
                                <!-- Category -->
                                <td class="hidden px-3 py-3 lg:table-cell sm:px-4">
                                    <span v-if="product.category?.name" class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:ring-purple-800">
                                        {{ product.category.name }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <!-- Price -->
                                <td class="px-3 py-3 text-right tabular-nums sm:px-4">{{ formatCurrency(product.price) }}</td>
                                <!-- Cost -->
                                <td class="hidden px-3 py-3 text-right tabular-nums md:table-cell sm:px-4">
                                    {{ product.cost_price ? formatCurrency(product.cost_price) : '—' }}
                                </td>
                                <!-- Status -->
                                <td class="px-3 py-3 text-center sm:px-4">
                                    <span
                                        :class="product.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800'
                                            : 'bg-gray-50 text-gray-600 ring-gray-200 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-700'"
                                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    >
                                        <span
                                            :class="product.is_active ? 'bg-emerald-500' : 'bg-gray-400'"
                                            class="h-1.5 w-1.5 rounded-full"
                                        />
                                        {{ product.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <!-- Actions -->
                                <td class="px-3 py-3 text-right sm:px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button v-if="product.sku" variant="ghost" size="icon" @click="showBarcode(product)" title="Print Barcode">
                                            <Barcode class="h-4 w-4" />
                                        </Button>
                                        <Button v-if="can('products.edit')" variant="ghost" size="icon" as-child>
                                            <Link :href="tenantUrl(`products/${product.id}/edit`)">
                                                <Edit class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            v-if="can('products.delete')"
                                            variant="ghost"
                                            size="icon"
                                            @click="confirmDelete(product)"
                                        >
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
                    <Pagination :data="products" />
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Product</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ productToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteProduct" :disabled="deleting">
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Barcode Dialog -->
        <Dialog v-model:open="barcodeDialog">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Product Barcode</DialogTitle>
                </DialogHeader>
                <div v-if="barcodeProduct?.sku" class="flex justify-center py-4">
                    <BarcodeLabel :sku="barcodeProduct.sku" :product-name="barcodeProduct.name" :price="barcodeProduct.price" />
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="barcodeDialog = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Upgrade Plan Modal -->
        <UpgradePlanModal
            v-model:open="upgradeModal"
            :current-plan-slug="currentPlanSlug"
            :plans="plans"
            resource="products"
        />
    </TenantLayout>
</template>
