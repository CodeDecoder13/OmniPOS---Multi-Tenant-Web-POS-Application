<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTenant } from '@/composables/useTenant';
import type { Shift, Branch, PaginatedData } from '@/types';

const props = defineProps<{
    shifts: PaginatedData<Shift>;
    filters: { status?: string; branch_id?: string; date_from?: string; date_to?: string };
    branches: Pick<Branch, 'id' | 'name'>[];
}>();

const { tenantUrl } = useTenant();

const statusFilter = ref(props.filters.status ?? 'all');
const branchFilter = ref(props.filters.branch_id ?? 'all');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

function applyFilters() {
    router.get(tenantUrl('shifts'), {
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        branch_id: branchFilter.value !== 'all' ? branchFilter.value : undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

watch([statusFilter, branchFilter, dateFrom, dateTo], applyFilters);

function formatCurrency(amount: string | number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(amount));
}

function formatDate(date: string | null) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function shiftDuration(shift: Shift): string {
    const start = new Date(shift.opened_at).getTime();
    const end = shift.closed_at ? new Date(shift.closed_at).getTime() : Date.now();
    const diff = Math.floor((end - start) / 1000);
    const hours = Math.floor(diff / 3600);
    const minutes = Math.floor((diff % 3600) / 60);
    return `${hours}h ${minutes}m`;
}

const breadcrumbs = [{ title: 'Shifts', href: tenantUrl('shifts') }];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <h1 class="text-2xl font-bold tracking-tight">Shifts</h1>

            <div class="flex flex-wrap items-center gap-4">
                <Select v-model="statusFilter">
                    <SelectTrigger class="w-[160px]">
                        <SelectValue placeholder="All Statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="open">Open</SelectItem>
                        <SelectItem value="closed">Closed</SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="branchFilter">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="All Branches" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Branches</SelectItem>
                        <SelectItem v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</SelectItem>
                    </SelectContent>
                </Select>
                <Input type="date" v-model="dateFrom" class="w-[160px]" placeholder="From date" />
                <Input type="date" v-model="dateTo" class="w-[160px]" placeholder="To date" />
            </div>

            <div class="rounded-md border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium">Operator</th>
                            <th class="px-4 py-3 text-left font-medium">Branch</th>
                            <th class="px-4 py-3 text-center font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Opened</th>
                            <th class="px-4 py-3 text-left font-medium">Closed</th>
                            <th class="px-4 py-3 text-center font-medium">Duration</th>
                            <th class="px-4 py-3 text-center font-medium">Orders</th>
                            <th class="px-4 py-3 text-right font-medium">Total Sales</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="shift in shifts.data" :key="shift.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ shift.operator?.name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ shift.branch?.name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <Badge :variant="shift.status === 'open' ? 'default' : 'secondary'" class="capitalize">
                                    {{ shift.status }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ formatDate(shift.opened_at) }}</td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ formatDate(shift.closed_at) }}</td>
                            <td class="px-4 py-3 text-center text-xs">{{ shiftDuration(shift) }}</td>
                            <td class="px-4 py-3 text-center">{{ shift.total_orders }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(shift.total_sales) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="tenantUrl(`shifts/${shift.id}`)">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="shifts.data.length === 0">
                            <td colspan="9" class="px-4 py-8 text-center text-muted-foreground">No shifts found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="shifts.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Showing {{ shifts.from }} to {{ shifts.to }} of {{ shifts.total }} shifts
                </p>
                <div class="flex gap-1">
                    <template v-for="link in shifts.links" :key="link.label">
                        <Button
                            v-if="link.url"
                            variant="outline"
                            size="sm"
                            :class="{ 'bg-primary text-primary-foreground': link.active }"
                            as-child
                        >
                            <Link :href="link.url" v-html="link.label" />
                        </Button>
                        <Button v-else variant="outline" size="sm" disabled v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
