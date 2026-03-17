<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, PaginatedData, Tenant } from '@/types';

defineProps<{
    tenants: PaginatedData<Tenant>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Tenants', href: '/admin/tenants' },
];

function toggleTenant(id: string) {
    router.patch(`/admin/tenants/${id}/toggle`);
}
</script>

<template>
    <Head title="Tenants" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">Tenants</h1>

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
                                <Button
                                    size="sm"
                                    :variant="tenant.is_active ? 'destructive' : 'default'"
                                    @click="toggleTenant(tenant.id)"
                                >
                                    {{ tenant.is_active ? 'Deactivate' : 'Activate' }}
                                </Button>
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
