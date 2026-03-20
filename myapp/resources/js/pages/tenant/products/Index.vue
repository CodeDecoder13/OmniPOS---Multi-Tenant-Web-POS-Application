<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Barcode, Edit, Package, Plus, Search, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
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
import type { BreadcrumbItem, Category, PaginatedData, Product } from '@/types';
import BarcodeLabel from '@/components/BarcodeLabel.vue';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps<{
    products: PaginatedData<Product>;
    categories: Pick<Category, 'id' | 'name'>[];
    filters: {
        search?: string;
        category_id?: string;
        is_active?: string;
    };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Products', href: tenantUrl('products') },
];

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

const barcodeDialog = ref(false);
const barcodeProduct = ref<Product | null>(null);

function showBarcode(product: Product) {
    barcodeProduct.value = product;
    barcodeDialog.value = true;
}

function formatPrice(value: string | number): string {
    return Number(value).toFixed(2);
}
</script>

<template>
    <Head title="Products" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Products</h1>
                <Button v-if="can('products.create')" as-child>
                    <Link :href="tenantUrl('products/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Product
                    </Link>
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px] max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Search by name or SKU..."
                        class="pl-9"
                    />
                </div>
                <Select v-model="categoryFilter">
                    <SelectTrigger class="w-[180px]">
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
                    <SelectTrigger class="w-[140px]">
                        <SelectValue placeholder="All Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="1">Active</SelectItem>
                        <SelectItem value="0">Inactive</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Empty state -->
            <div
                v-if="products.data.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border bg-white py-16 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="rounded-full bg-gray-100 p-4 dark:bg-gray-800">
                    <Package class="h-8 w-8 text-gray-400" />
                </div>
                <h3 class="mt-4 text-lg font-semibold">No products found</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ search || categoryFilter !== 'all' || statusFilter !== 'all' ? 'Try adjusting your filters.' : 'Create your first product to get started.' }}
                </p>
                <Button v-if="can('products.create') && !search && categoryFilter === 'all'" as-child class="mt-4">
                    <Link :href="tenantUrl('products/create')">Create Product</Link>
                </Button>
            </div>

            <!-- Table -->
            <div v-else class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="hidden px-4 py-3 text-left font-medium md:table-cell">SKU</th>
                            <th class="hidden px-4 py-3 text-left font-medium lg:table-cell">Category</th>
                            <th class="px-4 py-3 text-right font-medium">Price</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3 font-medium">{{ product.name }}</td>
                            <td class="hidden px-4 py-3 font-mono text-xs md:table-cell">{{ product.sku || '—' }}</td>
                            <td class="hidden px-4 py-3 lg:table-cell">{{ product.category?.name || '—' }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ formatPrice(product.price) }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="product.is_active ? 'default' : 'secondary'">
                                    {{ product.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
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
                                        <Trash2 class="h-4 w-4 text-red-500" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="products.last_page > 1" class="flex items-center justify-between border-t px-4 py-3 dark:border-gray-800">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ products.from }} to {{ products.to }} of {{ products.total }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in products.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="rounded-md px-3 py-1 text-sm"
                                :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                        </template>
                    </div>
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
                <DialogFooter>
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
    </TenantLayout>
</template>
