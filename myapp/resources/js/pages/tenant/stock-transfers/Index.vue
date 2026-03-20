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
import type { StockTransfer, Branch, PaginatedData } from '@/types';

const props = defineProps<{
    transfers: PaginatedData<StockTransfer>;
    branches: Pick<Branch, 'id' | 'name'>[];
    filters: { search?: string; status?: string; branch_id?: string };
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

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

function statusVariant(status: string) {
    const map: Record<string, string> = {
        pending: 'secondary',
        in_transit: 'default',
        completed: 'default',
        cancelled: 'destructive',
    };
    return (map[status] ?? 'secondary') as 'default' | 'secondary' | 'destructive';
}

function statusLabel(status: string) {
    const map: Record<string, string> = { pending: 'Pending', in_transit: 'In Transit', completed: 'Completed', cancelled: 'Cancelled' };
    return map[status] ?? status;
}

const breadcrumbs = [{ title: 'Stock Transfers', href: tenantUrl('stock-transfers') }];
</script>

<template>
    <TenantLayout title="Stock Transfers" :breadcrumbs="breadcrumbs">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Stock Transfers</h1>
                <Button v-if="can('inventory.manage')" as-child>
                    <Link :href="tenantUrl('stock-transfers-create')">
                        <Plus class="mr-2 h-4 w-4" />
                        New Transfer
                    </Link>
                </Button>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search by transfer #..." class="pl-9" />
                </div>
                <Select :model-value="statusFilter || 'all'" @update:model-value="onStatusChange">
                    <SelectTrigger class="w-[160px]">
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
            </div>

            <div class="rounded-md border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Transfer #</th>
                            <th class="px-4 py-3 text-left font-medium">From</th>
                            <th class="px-4 py-3 text-left font-medium">To</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Created By</th>
                            <th class="px-4 py-3 text-left font-medium">Date</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="transfer in transfers.data" :key="transfer.id" class="border-b">
                            <td class="px-4 py-3 font-mono text-xs font-medium">{{ transfer.transfer_number }}</td>
                            <td class="px-4 py-3">{{ transfer.source_branch?.name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ transfer.destination_branch?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="statusVariant(transfer.status)">{{ statusLabel(transfer.status) }}</Badge>
                            </td>
                            <td class="px-4 py-3">{{ transfer.creator?.name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ new Date(transfer.created_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="tenantUrl(`stock-transfers/${transfer.id}`)">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="transfers.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No stock transfers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :data="transfers" />
        </div>
    </TenantLayout>
</template>
