<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Eye, Plus, Search } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Pagination from '@/components/Pagination.vue';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import type { PurchaseOrder, Supplier, Branch, PaginatedData } from '@/types';

const props = defineProps<{
    purchaseOrders: PaginatedData<PurchaseOrder>;
    suppliers: Pick<Supplier, 'id' | 'name'>[];
    branches: Pick<Branch, 'id' | 'name'>[];
    filters: { search?: string; status?: string; supplier_id?: string; branch_id?: string };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const supplierFilter = ref(props.filters.supplier_id ?? '');
const branchFilter = ref(props.filters.branch_id ?? '');

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

function statusVariant(status: string) {
    const map: Record<string, string> = { draft: 'secondary', sent: 'default', partial: 'default', received: 'default', cancelled: 'destructive' };
    return (map[status] ?? 'secondary') as 'default' | 'secondary' | 'destructive';
}

function statusLabel(status: string) {
    const map: Record<string, string> = { draft: 'Draft', sent: 'Sent', partial: 'Partial', received: 'Received', cancelled: 'Cancelled' };
    return map[status] ?? status;
}

const breadcrumbs = [{ title: 'Purchase Orders', href: tenantUrl('purchase-orders') }];
</script>

<template>
    <TenantLayout title="Purchase Orders" :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Purchase Orders</h1>
                <Button v-if="can('inventory.manage')" as-child>
                    <Link :href="tenantUrl('purchase-orders-create')">
                        <Plus class="mr-2 h-4 w-4" />
                        New PO
                    </Link>
                </Button>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search PO #..." class="pl-9" />
                </div>
                <Select :model-value="statusFilter || 'all'" @update:model-value="v => onFilterChange('status', v)">
                    <SelectTrigger class="w-[140px]"><SelectValue placeholder="Status" /></SelectTrigger>
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
                    <SelectTrigger class="w-[160px]"><SelectValue placeholder="Supplier" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Suppliers</SelectItem>
                        <SelectItem v-for="s in suppliers" :key="s.id" :value="String(s.id)">{{ s.name }}</SelectItem>
                    </SelectContent>
                </Select>
                <Select :model-value="branchFilter || 'all'" @update:model-value="v => onFilterChange('branch_id', v)">
                    <SelectTrigger class="w-[160px]"><SelectValue placeholder="Branch" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Branches</SelectItem>
                        <SelectItem v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="rounded-md border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">PO #</th>
                            <th class="px-4 py-3 text-left font-medium">Supplier</th>
                            <th class="px-4 py-3 text-left font-medium">Branch</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Date</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="po in purchaseOrders.data" :key="po.id" class="border-b">
                            <td class="px-4 py-3 font-mono text-xs font-medium">{{ po.po_number }}</td>
                            <td class="px-4 py-3">{{ po.supplier?.name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ po.branch?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ Number(po.total_amount).toFixed(2) }}</td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="statusVariant(po.status)">{{ statusLabel(po.status) }}</Badge>
                            </td>
                            <td class="px-4 py-3">{{ new Date(po.created_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="tenantUrl(`purchase-orders/${po.id}`)">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="purchaseOrders.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No purchase orders found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :data="purchaseOrders" />
        </div>
    </TenantLayout>
</template>
