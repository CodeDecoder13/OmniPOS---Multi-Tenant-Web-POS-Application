<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Search, Trash2 } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
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

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.is_active ?? '');
const deleteDialog = ref(false);
const promoToDelete = ref<Promotion | null>(null);

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

function formatType(type: string) {
    if (type === 'percentage') return 'Percent';
    if (type === 'fixed') return 'Fixed';
    if (type === 'buy_x_get_y') return 'Buy X Get Y';
    return type;
}

function formatValue(promo: Promotion) {
    if (promo.type === 'percentage') return `${promo.value}%`;
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
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Promotions</h1>
                <Button v-if="can('promotions.create')" as-child>
                    <Link :href="tenantUrl('promotions/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Promotion
                    </Link>
                </Button>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search by code or name..." class="pl-9" />
                </div>
                <Select :model-value="statusFilter || 'all'" @update:model-value="onStatusChange">
                    <SelectTrigger class="w-[160px]">
                        <SelectValue placeholder="All Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="1">Active</SelectItem>
                        <SelectItem value="0">Inactive</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="rounded-md border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Code</th>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-center font-medium">Type</th>
                            <th class="px-4 py-3 text-center font-medium">Value</th>
                            <th class="px-4 py-3 text-center font-medium">Dates</th>
                            <th class="px-4 py-3 text-center font-medium">Usage</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="promo in promotions.data" :key="promo.id" class="border-b">
                            <td class="px-4 py-3 font-mono font-medium">{{ promo.code }}</td>
                            <td class="px-4 py-3">{{ promo.name }}</td>
                            <td class="px-4 py-3 text-center">
                                <Badge variant="outline">{{ formatType(promo.type) }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-center font-medium">{{ formatValue(promo) }}</td>
                            <td class="px-4 py-3 text-center text-xs text-muted-foreground">
                                {{ formatDate(promo.start_date) }} — {{ formatDate(promo.end_date) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ promo.used_count }}{{ promo.usage_limit ? ` / ${promo.usage_limit}` : '' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="promo.is_active ? 'default' : 'secondary'">
                                    {{ promo.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button v-if="can('promotions.edit')" variant="ghost" size="icon" as-child>
                                        <Link :href="tenantUrl(`promotions/${promo.id}/edit`)">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button v-if="can('promotions.delete')" variant="ghost" size="icon" @click="confirmDelete(promo)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="promotions.data.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">No promotions found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :data="promotions" />
        </div>

        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Promotion</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ promoToDelete?.code }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deletePromo">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>
