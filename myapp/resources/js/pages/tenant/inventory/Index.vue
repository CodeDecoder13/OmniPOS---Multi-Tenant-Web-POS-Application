<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { History, Package, Search, SlidersHorizontal, Warehouse } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
import type { Branch, BreadcrumbItem, Inventory, InventoryAdjustment, PaginatedData } from '@/types';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps<{
    inventory: PaginatedData<Inventory>;
    branches: Pick<Branch, 'id' | 'name'>[];
    filters: {
        search?: string;
        branch_id?: string;
        low_stock?: string;
    };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Inventory', href: tenantUrl('inventory') },
];

// Filters
const search = ref(props.filters.search ?? '');
const branchFilter = ref(props.filters.branch_id ?? 'all');
const lowStockFilter = ref(props.filters.low_stock === '1');

let searchTimeout: ReturnType<typeof setTimeout>;

function applyFilters() {
    const params: Record<string, string> = {};
    if (search.value) params.search = search.value;
    if (branchFilter.value && branchFilter.value !== 'all') params.branch_id = branchFilter.value;
    if (lowStockFilter.value) params.low_stock = '1';

    router.get(tenantUrl('inventory'), params, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch([branchFilter], () => {
    applyFilters();
});

function toggleLowStock() {
    lowStockFilter.value = !lowStockFilter.value;
    applyFilters();
}

// Adjust Dialog
const adjustDialog = ref(false);
const adjustItem = ref<Inventory | null>(null);
const adjustForm = ref({
    type: 'purchase',
    quantity_change: 0,
    reason: '',
    low_stock_threshold: 0,
});
const adjusting = ref(false);

function openAdjust(item: Inventory) {
    adjustItem.value = item;
    adjustForm.value = {
        type: 'purchase',
        quantity_change: 0,
        reason: '',
        low_stock_threshold: item.low_stock_threshold,
    };
    adjustDialog.value = true;
}

const resultingStock = computed(() => {
    if (!adjustItem.value) return 0;
    return adjustItem.value.quantity_on_hand + (adjustForm.value.quantity_change || 0);
});

function submitAdjust() {
    if (!adjustItem.value || adjustForm.value.quantity_change === 0) return;
    adjusting.value = true;
    router.post(tenantUrl(`inventory/${adjustItem.value.id}/adjust`), adjustForm.value as any, {
        onFinish: () => {
            adjusting.value = false;
            adjustDialog.value = false;
            adjustItem.value = null;
        },
    });
}

// History Dialog
const historyDialog = ref(false);
const historyItem = ref<Inventory | null>(null);
const historyData = ref<InventoryAdjustment[]>([]);
const historyLoading = ref(false);
const historyHasMore = ref(false);
const historyPage = ref(1);

async function openHistory(item: Inventory) {
    historyItem.value = item;
    historyData.value = [];
    historyPage.value = 1;
    historyDialog.value = true;
    await loadHistory();
}

async function loadHistory() {
    if (!historyItem.value) return;
    historyLoading.value = true;
    try {
        const res = await fetch(tenantUrl(`inventory/${historyItem.value.id}/history?page=${historyPage.value}`));
        const json = await res.json();
        historyData.value.push(...json.data);
        historyHasMore.value = json.current_page < json.last_page;
    } finally {
        historyLoading.value = false;
    }
}

async function loadMoreHistory() {
    historyPage.value++;
    await loadHistory();
}

function isLowStock(item: Inventory): boolean {
    return item.low_stock_threshold > 0 && item.quantity_on_hand <= item.low_stock_threshold;
}

const adjustmentTypes = [
    { value: 'purchase', label: 'Purchase' },
    { value: 'damage', label: 'Damage' },
    { value: 'correction', label: 'Correction' },
    { value: 'initial', label: 'Initial' },
];

function typeLabel(type: string): string {
    const labels: Record<string, string> = {
        purchase: 'Purchase',
        sale: 'Sale',
        return: 'Return',
        damage: 'Damage',
        correction: 'Correction',
        initial: 'Initial',
    };
    return labels[type] ?? type;
}

function typeBadgeVariant(type: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (type) {
        case 'purchase':
        case 'initial':
            return 'default';
        case 'sale':
            return 'secondary';
        case 'return':
            return 'outline';
        case 'damage':
            return 'destructive';
        default:
            return 'secondary';
    }
}
</script>

<template>
    <Head title="Inventory" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Inventory</h1>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px] max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Search by product name or SKU..."
                        class="pl-9"
                    />
                </div>
                <Select v-model="branchFilter">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="All Branches" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Branches</SelectItem>
                        <SelectItem v-for="branch in branches" :key="branch.id" :value="String(branch.id)">
                            {{ branch.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button
                    :variant="lowStockFilter ? 'default' : 'outline'"
                    size="sm"
                    @click="toggleLowStock"
                >
                    <SlidersHorizontal class="mr-2 h-4 w-4" />
                    Low Stock Only
                </Button>
            </div>

            <!-- Empty state -->
            <div
                v-if="inventory.data.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border bg-white py-16 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="rounded-full bg-gray-100 p-4 dark:bg-gray-800">
                    <Warehouse class="h-8 w-8 text-gray-400" />
                </div>
                <h3 class="mt-4 text-lg font-semibold">No inventory records found</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ search || branchFilter !== 'all' || lowStockFilter ? 'Try adjusting your filters.' : 'Inventory records are created automatically when products are sold via POS, or you can add stock manually.' }}
                </p>
            </div>

            <!-- Table -->
            <div v-else class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Product</th>
                            <th class="hidden px-4 py-3 text-left font-medium md:table-cell">Branch</th>
                            <th class="px-4 py-3 text-right font-medium">Stock</th>
                            <th class="hidden px-4 py-3 text-right font-medium lg:table-cell">Low Stock Threshold</th>
                            <th class="hidden px-4 py-3 text-left font-medium lg:table-cell">Last Updated</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in inventory.data"
                            :key="item.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3">
                                <div>
                                    <span class="font-medium">{{ item.product?.name ?? '—' }}</span>
                                    <span v-if="item.product?.sku" class="ml-2 font-mono text-xs text-muted-foreground">{{ item.product.sku }}</span>
                                </div>
                                <span class="text-xs text-muted-foreground md:hidden">{{ item.branch?.name }}</span>
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">{{ item.branch?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-mono font-semibold" :class="isLowStock(item) ? 'text-red-600' : 'text-green-600'">
                                {{ item.quantity_on_hand }}
                            </td>
                            <td class="hidden px-4 py-3 text-right font-mono lg:table-cell">
                                {{ item.low_stock_threshold > 0 ? item.low_stock_threshold : '—' }}
                            </td>
                            <td class="hidden px-4 py-3 text-muted-foreground lg:table-cell">
                                {{ new Date(item.updated_at).toLocaleDateString() }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button
                                        v-if="can('inventory.manage')"
                                        variant="ghost"
                                        size="sm"
                                        @click="openAdjust(item)"
                                    >
                                        <SlidersHorizontal class="mr-1 h-4 w-4" />
                                        Adjust
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="openHistory(item)"
                                    >
                                        <History class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="inventory.last_page > 1" class="flex items-center justify-between border-t px-4 py-3 dark:border-gray-800">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ inventory.from }} to {{ inventory.to }} of {{ inventory.total }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in inventory.links" :key="link.label">
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

        <!-- Adjust Dialog -->
        <Dialog v-model:open="adjustDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Adjust Inventory</DialogTitle>
                    <DialogDescription>
                        {{ adjustItem?.product?.name }} — {{ adjustItem?.branch?.name }}
                    </DialogDescription>
                </DialogHeader>

                <div class="flex flex-col gap-4 py-2">
                    <div>
                        <Label class="text-sm text-muted-foreground">Current Stock</Label>
                        <p class="text-lg font-semibold font-mono">{{ adjustItem?.quantity_on_hand }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Adjustment Type</Label>
                        <Select v-model="adjustForm.type">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="t in adjustmentTypes" :key="t.value" :value="t.value">
                                    {{ t.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Quantity Change</Label>
                        <Input
                            v-model.number="adjustForm.quantity_change"
                            type="number"
                            placeholder="e.g. 10 or -5"
                        />
                        <p class="text-xs text-muted-foreground">Use negative numbers to decrease stock.</p>
                    </div>

                    <div class="rounded-md bg-gray-50 px-3 py-2 dark:bg-gray-800">
                        <span class="text-sm text-muted-foreground">Resulting stock: </span>
                        <span class="font-mono font-semibold" :class="resultingStock < 0 ? 'text-red-600' : 'text-green-600'">
                            {{ resultingStock }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Reason <span class="text-muted-foreground">(optional)</span></Label>
                        <Textarea
                            v-model="adjustForm.reason"
                            placeholder="Why is this adjustment being made?"
                            rows="2"
                        />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Low Stock Threshold</Label>
                        <Input
                            v-model.number="adjustForm.low_stock_threshold"
                            type="number"
                            min="0"
                            placeholder="0"
                        />
                        <p class="text-xs text-muted-foreground">Alert when stock falls to or below this level. Set to 0 to disable.</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="adjustDialog = false">Cancel</Button>
                    <Button @click="submitAdjust" :disabled="adjusting || adjustForm.quantity_change === 0">
                        {{ adjusting ? 'Saving...' : 'Apply Adjustment' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- History Dialog -->
        <Dialog v-model:open="historyDialog">
            <DialogContent class="sm:max-w-lg max-h-[80vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Adjustment History</DialogTitle>
                    <DialogDescription>
                        {{ historyItem?.product?.name }} — {{ historyItem?.branch?.name }}
                    </DialogDescription>
                </DialogHeader>

                <div class="flex-1 overflow-y-auto -mx-6 px-6">
                    <div v-if="historyData.length === 0 && !historyLoading" class="py-8 text-center text-sm text-muted-foreground">
                        No adjustments recorded yet.
                    </div>

                    <div v-else class="flex flex-col gap-3">
                        <div
                            v-for="adj in historyData"
                            :key="adj.id"
                            class="flex items-start gap-3 rounded-lg border px-3 py-2.5 text-sm dark:border-gray-800"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <Badge :variant="typeBadgeVariant(adj.type)" class="text-xs">
                                        {{ typeLabel(adj.type) }}
                                    </Badge>
                                    <span class="font-mono font-semibold" :class="adj.quantity_change > 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ adj.quantity_change > 0 ? '+' : '' }}{{ adj.quantity_change }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ adj.quantity_before }} → {{ adj.quantity_after }}
                                    </span>
                                </div>
                                <p v-if="adj.reason" class="mt-1 text-xs text-muted-foreground truncate">{{ adj.reason }}</p>
                                <div class="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                                    <span>{{ new Date(adj.created_at).toLocaleString() }}</span>
                                    <span v-if="adj.creator">by {{ adj.creator.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="historyLoading" class="py-4 text-center text-sm text-muted-foreground">
                        Loading...
                    </div>

                    <div v-if="historyHasMore && !historyLoading" class="py-3 text-center">
                        <Button variant="outline" size="sm" @click="loadMoreHistory">Load More</Button>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="historyDialog = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
