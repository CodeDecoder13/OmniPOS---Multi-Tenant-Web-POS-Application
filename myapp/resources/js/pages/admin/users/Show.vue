<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, Tenant } from '@/types';
import type { User } from '@/types/auth';

const props = defineProps<{
    user: User & { tenants?: Tenant[] };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
    { title: props.user.name, href: `/admin/users/${props.user.id}` },
];

function toggleVerified() {
    router.patch(`/admin/users/${props.user.id}/toggle-verified`);
}

function resetPassword() {
    if (!confirm('Reset this user\'s password?')) return;
    router.post(`/admin/users/${props.user.id}/reset-password`);
}
</script>

<template>
    <Head :title="`User: ${user.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ user.name }}</h1>
                    <p class="text-muted-foreground">{{ user.email }}</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="`/admin/users/${user.id}/edit`">Edit</Link>
                    </Button>
                    <Button variant="outline" @click="resetPassword">Reset Password</Button>
                    <Button
                        :variant="user.email_verified_at ? 'destructive' : 'default'"
                        @click="toggleVerified"
                    >
                        {{ user.email_verified_at ? 'Unverify' : 'Verify' }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-semibold">Details</h2>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-muted-foreground">Email Verified</dt>
                            <dd>
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="user.email_verified_at
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'"
                                >
                                    {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Joined</dt>
                            <dd>{{ new Date(user.created_at).toLocaleDateString() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Tenants -->
            <div class="rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-lg font-semibold">Tenants ({{ user.tenants?.length ?? 0 }})</h2>
                <table v-if="user.tenants && user.tenants.length > 0" class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-800">
                            <th class="px-4 py-2 text-left font-medium">Name</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="tenant in user.tenants" :key="tenant.id" class="border-b last:border-0 dark:border-gray-800">
                            <td class="px-4 py-2">
                                <Link :href="`/admin/tenants/${tenant.id}`" class="text-teal-600 hover:underline">
                                    {{ tenant.name }}
                                </Link>
                            </td>
                            <td class="px-4 py-2">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="tenant.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                >
                                    {{ tenant.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="`/admin/tenants/${tenant.id}`">View</Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-muted-foreground">Not associated with any tenants.</p>
            </div>
        </div>
    </AdminLayout>
</template>
