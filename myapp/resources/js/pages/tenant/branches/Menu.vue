<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { RotateCcw, Save, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Branch, BranchMenuProduct, BreadcrumbItem } from '@/types';
import { useTenant } from '@/composables/useTenant';

const props = defineProps<{
    branch: Branch;
    products: BranchMenuProduct[];
    categories: { id: number; name: string }[];
}>();

const { tenantUrl } = useTenant();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Branches', href: tenantUrl('branches') },
    { title: props.branch.name, href: tenantUrl(`branches/${props.branch.id}/edit`) },
    { title: 'Menu & Pricing', href: tenantUrl(`branches/${props.branch.id}/menu`) },
];

const search = ref('');
const categoryFilter = ref<string>('all');
const saving = ref(false);

// Track overrides locally
interface ProductOverride {
    custom_price: string;
    is_available: boolean;
}

const overrides = ref<Record<number, ProductOverride>>({});

// Initialize overrides from server data
props.products.forEach((product) => {
    overrides.value[product.id] = {
        custom_price: product.custom_price ?? '',
        is_available: product.is_available,
    };
});

const filteredProducts = computed(() => {
    let items = props.products;

    if (search.value) {
        const s = search.value.toLowerCase();
        items = items.filter(
            (p) =>
                p.name.toLowerCase().includes(s) ||
                (p.sku && p.sku.toLowerCase().includes(s)),
        );
    }

    if (categoryFilter.value && categoryFilter.value !== 'all') {
        const catId = parseInt(categoryFilter.value);
        items = items.filter((p) => p.category?.id === catId);
    }

    return items;
});

const hasChanges = computed(() => {
    return props.products.some((product) => {
        const override = overrides.value[product.id];
        if (!override) return false;

        const originalPrice = product.custom_price ?? '';
        const originalAvailable = product.is_available;

        return override.custom_price !== originalPrice || override.is_available !== originalAvailable;
    });
});

function getEffectivePrice(product: BranchMenuProduct): string {
    const override = overrides.value[product.id];
    if (override && override.custom_price !== '') {
        return parseFloat(override.custom_price).toFixed(2);
    }
    return parseFloat(product.price).toFixed(2);
}

function resetAll() {
    props.products.forEach((product) => {
        overrides.value[product.id] = {
            custom_price: '',
            is_available: true,
        };
    });
}

function saveChanges() {
    saving.value = true;

    const items = props.products.map((product) => {
        const override = overrides.value[product.id];
        return {
            product_id: product.id,
            custom_price: override.custom_price !== '' ? parseFloat(override.custom_price) : null,
            is_available: override.is_available,
        };
    });

    router.put(
        tenantUrl(`branches/${props.branch.id}/menu`),
        { items },
        {
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="`${branch.name} — Menu & Pricing`" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Menu & Pricing</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage product availability and custom pricing for <strong>{{ branch.name }}</strong>
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="resetAll">
                        <RotateCcw class="mr-2 h-4 w-4" />
                        Reset All
                    </Button>
                    <Button @click="saveChanges" :disabled="saving || !hasChanges">
                        <Save class="mr-2 h-4 w-4" />
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Search products..."
                        class="pl-9"
                    />
                </div>
                <Select v-model="categoryFilter">
                    <SelectTrigger class="w-full sm:w-48">
                        <SelectValue placeholder="All Categories" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Categories</SelectItem>
                        <SelectItem
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="String(cat.id)"
                        >
                            {{ cat.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Products Table -->
            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <div v-if="filteredProducts.length === 0" class="py-12 text-center text-muted-foreground">
                    No products found.
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Product</th>
                            <th class="hidden px-4 py-3 text-left font-medium md:table-cell">SKU</th>
                            <th class="hidden px-4 py-3 text-left font-medium lg:table-cell">Category</th>
                            <th class="px-4 py-3 text-right font-medium">Global Price</th>
                            <th class="px-4 py-3 text-right font-medium">Branch Price</th>
                            <th class="px-4 py-3 text-right font-medium">Effective</th>
                            <th class="px-4 py-3 text-center font-medium">Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="border-b last:border-0 dark:border-gray-800"
                            :class="{ 'opacity-50': !overrides[product.id]?.is_available }"
                        >
                            <td class="px-4 py-3 font-medium">{{ product.name }}</td>
                            <td class="hidden px-4 py-3 font-mono text-xs md:table-cell">{{ product.sku || '—' }}</td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                <Badge v-if="product.category" variant="secondary">{{ product.category.name }}</Badge>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ parseFloat(product.price).toFixed(2) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Input
                                    v-model="overrides[product.id].custom_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="Use global"
                                    class="ml-auto w-28 text-right"
                                />
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-medium">
                                <span
                                    :class="overrides[product.id]?.custom_price !== '' ? 'text-blue-600 dark:text-blue-400' : ''"
                                >
                                    {{ getEffectivePrice(product) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <Switch
                                    :checked="overrides[product.id]?.is_available"
                                    @update:checked="overrides[product.id].is_available = $event"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </TenantLayout>
</template>
