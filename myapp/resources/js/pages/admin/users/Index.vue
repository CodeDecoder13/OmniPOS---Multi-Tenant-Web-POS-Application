<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem, PaginatedData, User } from '@/types';

defineProps<{
    users: PaginatedData<User>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
];
</script>

<template>
    <Head title="Users" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">Users</h1>

            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Email</th>
                            <th class="px-4 py-3 text-left font-medium">Verified</th>
                            <th class="px-4 py-3 text-left font-medium">Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="user.email_verified_at
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'"
                                >
                                    {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ new Date(user.created_at).toLocaleDateString() }}
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                                No users found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in users.links" :key="link.label">
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
