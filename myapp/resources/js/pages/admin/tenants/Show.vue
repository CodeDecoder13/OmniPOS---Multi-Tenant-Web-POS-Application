<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, Tenant } from '@/types';

const props = defineProps<{
    tenant: Tenant;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Tenants', href: '/admin/tenants' },
    { title: props.tenant.name, href: `/admin/tenants/${props.tenant.id}` },
];

function toggleTenant() {
    router.patch(`/admin/tenants/${props.tenant.id}/toggle`);
}

function deleteTenant() {
    if (!confirm('Are you sure you want to delete this tenant?')) return;
    router.delete(`/admin/tenants/${props.tenant.id}`);
}
</script>

<template>
    <Head :title="`Tenant: ${tenant.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ tenant.name }}</h1>
                    <p class="text-muted-foreground">{{ tenant.slug }}</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="`/admin/tenants/${tenant.id}/edit`">Edit</Link>
                    </Button>
                    <Button
                        :variant="tenant.is_active ? 'destructive' : 'default'"
                        @click="toggleTenant"
                    >
                        {{ tenant.is_active ? 'Deactivate' : 'Activate' }}
                    </Button>
                    <Button variant="destructive" @click="deleteTenant">Delete</Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Details -->
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Details</h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-muted-foreground">Status</dt>
                            <dd>
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="tenant.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                >
                                    {{ tenant.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Business Type</dt>
                            <dd class="font-medium capitalize">{{ tenant.business_type }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Owner</dt>
                            <dd class="font-medium">{{ tenant.owner?.name ?? 'N/A' }}</dd>
                            <dd class="text-sm text-muted-foreground">{{ tenant.owner?.email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Created</dt>
                            <dd>{{ new Date(tenant.created_at).toLocaleDateString() }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Subscription -->
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Subscription</h2>
                        <Button v-if="tenant.subscription" size="sm" variant="outline" as-child>
                            <Link :href="`/admin/subscriptions/${tenant.subscription.id}`">Manage</Link>
                        </Button>
                    </div>
                    <dl v-if="tenant.subscription" class="space-y-3">
                        <div>
                            <dt class="text-sm text-muted-foreground">Plan</dt>
                            <dd class="font-medium">{{ tenant.subscription.plan?.name ?? 'Unknown' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Price</dt>
                            <dd>&#8369;{{ Number(tenant.subscription.plan?.price ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}/mo</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Status</dt>
                            <dd>
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    {{ tenant.subscription.status }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                    <p v-else class="text-muted-foreground">No active subscription.</p>
                </div>
            </div>

            <!-- Users -->
            <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-lg font-semibold">Users ({{ tenant.users?.length ?? 0 }})</h2>
                <table v-if="tenant.users && tenant.users.length > 0" class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-800">
                            <th class="px-4 py-2 text-left font-medium">Name</th>
                            <th class="px-4 py-2 text-left font-medium">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in tenant.users" :key="user.id" class="border-b last:border-0 dark:border-gray-800">
                            <td class="px-4 py-2">{{ user.name }}</td>
                            <td class="px-4 py-2 text-muted-foreground">{{ user.email }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-muted-foreground">No users.</p>
            </div>
        </div>
    </AdminLayout>
</template>
