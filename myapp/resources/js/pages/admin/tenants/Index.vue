<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem, PaginatedData, Tenant } from '@/types';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    tenants: PaginatedData<Tenant>;
    filters: { search?: string; is_active?: string; business_type?: string; plan_id?: string };
    plans: { id: number; name: string }[];
    businessTypes: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Tenants', href: '/admin/tenants' },
];

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.is_active ?? '');
const typeFilter = ref(props.filters.business_type ?? '');
const planFilter = ref(props.filters.plan_id ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;

watch(search, (val) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters(), 300);
});

function applyFilters() {
    router.get('/admin/tenants', {
        search: search.value || undefined,
        is_active: statusFilter.value || undefined,
        business_type: typeFilter.value || undefined,
        plan_id: planFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function toggleTenant(id: string) {
    router.patch(`/admin/tenants/${id}/toggle`);
}

function deleteTenant(id: string) {
    if (!confirm('Are you sure you want to delete this tenant? This action cannot be undone.')) return;
    router.delete(`/admin/tenants/${id}`);
}
</script>

<template>
    <Head title="Tenants" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Tenants</h1>
                <Button as-child class="bg-teal-600 hover:bg-teal-700">
                    <Link href="/admin/tenants/create">
                        <Plus class="mr-1 size-4" />
                        Create Tenant
                    </Link>
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <Input
                    v-model="search"
                    placeholder="Search tenants..."
                    class="w-64"
                />
                <select
                    v-model="statusFilter"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    @change="applyFilters"
                >
                    <option value="">All Statuses</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <select
                    v-model="typeFilter"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    @change="applyFilters"
                >
                    <option value="">All Types</option>
                    <option v-for="bt in businessTypes" :key="bt.value" :value="bt.value">{{ bt.label }}</option>
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
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Owner</th>
                            <th class="px-4 py-3 text-left font-medium">Plan</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Created</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="tenant in tenants.data"
                            :key="tenant.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3">
                                <Link :href="`/admin/tenants/${tenant.id}`" class="font-medium text-teal-600 hover:underline">
                                    {{ tenant.name }}
                                </Link>
                                <div class="text-xs text-muted-foreground">{{ tenant.slug }}</div>
                            </td>
                            <td class="px-4 py-3">{{ tenant.owner?.name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ tenant.subscription?.plan?.name ?? 'None' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="tenant.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                >
                                    {{ tenant.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ new Date(tenant.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button size="sm" variant="outline" as-child>
                                        <Link :href="`/admin/tenants/${tenant.id}/edit`">Edit</Link>
                                    </Button>
                                    <Button
                                        size="sm"
                                        :variant="tenant.is_active ? 'destructive' : 'default'"
                                        @click="toggleTenant(tenant.id)"
                                    >
                                        {{ tenant.is_active ? 'Deactivate' : 'Activate' }}
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deleteTenant(tenant.id)">
                                        Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="tenants.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                No tenants found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="tenants.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in tenants.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg px-3 py-1.5 text-sm"
                        :class="link.active
                            ? 'bg-teal-600 text-white'
                            : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-lg px-3 py-1.5 text-sm text-muted-foreground"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
