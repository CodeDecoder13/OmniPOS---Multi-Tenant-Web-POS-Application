<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem, PaginatedData, TenantSubscription } from '@/types';
import { ref, watch } from 'vue';

const props = defineProps<{
    subscriptions: PaginatedData<TenantSubscription & { tenant?: { id: string; name: string } }>;
    filters: { search?: string; status?: string; plan_id?: string };
    plans: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Subscriptions', href: '/admin/subscriptions' },
];

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const planFilter = ref(props.filters.plan_id ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters(), 300);
});

function applyFilters() {
    router.get('/admin/subscriptions', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        plan_id: planFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function statusColor(status: string): string {
    const colors: Record<string, string> = {
        active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        trial: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        expired: 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400',
        past_due: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    };
    return colors[status] ?? colors.expired;
}
</script>

<template>
    <Head title="Subscriptions" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">Subscriptions</h1>

            <div class="flex flex-wrap items-center gap-3">
                <Input v-model="search" placeholder="Search by tenant..." class="w-64" />
                <select
                    v-model="statusFilter"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    @change="applyFilters"
                >
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="trial">Trial</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="expired">Expired</option>
                    <option value="past_due">Past Due</option>
                </select>
                <select
                    v-model="planFilter"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    @change="applyFilters"
                >
                    <option value="">All Plans</option>
                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                </select>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Tenant</th>
                            <th class="px-4 py-3 text-left font-medium">Plan</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Trial Ends</th>
                            <th class="px-4 py-3 text-left font-medium">Created</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="sub in subscriptions.data"
                            :key="sub.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3">
                                <Link v-if="sub.tenant" :href="`/admin/tenants/${sub.tenant.id}`" class="font-medium text-teal-600 hover:underline">
                                    {{ sub.tenant.name }}
                                </Link>
                                <span v-else class="text-muted-foreground">N/A</span>
                            </td>
                            <td class="px-4 py-3">{{ sub.plan?.name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" :class="statusColor(sub.status)">
                                    {{ sub.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ sub.trial_ends_at ? new Date(sub.trial_ends_at).toLocaleDateString() : '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ new Date(sub.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="`/admin/subscriptions/${sub.id}`">Manage</Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="subscriptions.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                No subscriptions found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="subscriptions.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in subscriptions.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg px-3 py-1.5 text-sm"
                        :class="link.active ? 'bg-teal-600 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                        v-html="link.label"
                    />
                    <span v-else class="rounded-lg px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
