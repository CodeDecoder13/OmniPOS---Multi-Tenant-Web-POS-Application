<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem, PaginatedData } from '@/types';
import type { User } from '@/types/auth';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    users: PaginatedData<User & { tenants_count?: number }>;
    filters: { search?: string; verified?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
];

const search = ref(props.filters.search ?? '');
const verifiedFilter = ref(props.filters.verified ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters(), 300);
});

function applyFilters() {
    router.get('/admin/users', {
        search: search.value || undefined,
        verified: verifiedFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function deleteUser(id: number) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    router.delete(`/admin/users/${id}`);
}

function toggleVerified(id: number) {
    router.patch(`/admin/users/${id}/toggle-verified`);
}

function resetPassword(id: number) {
    if (!confirm('Reset this user\'s password? A new random password will be generated.')) return;
    router.post(`/admin/users/${id}/reset-password`);
}
</script>

<template>
    <Head title="Users" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Users</h1>
                <Button as-child class="bg-teal-600 hover:bg-teal-700">
                    <Link href="/admin/users/create">
                        <Plus class="mr-1 size-4" />
                        Create User
                    </Link>
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <Input
                    v-model="search"
                    placeholder="Search users..."
                    class="w-64"
                />
                <select
                    v-model="verifiedFilter"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    @change="applyFilters"
                >
                    <option value="">All Users</option>
                    <option value="1">Verified</option>
                    <option value="0">Unverified</option>
                </select>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Email</th>
                            <th class="px-4 py-3 text-left font-medium">Tenants</th>
                            <th class="px-4 py-3 text-left font-medium">Verified</th>
                            <th class="px-4 py-3 text-left font-medium">Joined</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3">
                                <Link :href="`/admin/users/${user.id}`" class="font-medium text-teal-600 hover:underline">
                                    {{ user.name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                            <td class="px-4 py-3">{{ (user as any).tenants_count ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium cursor-pointer"
                                    :class="user.email_verified_at
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'"
                                    @click="toggleVerified(user.id)"
                                >
                                    {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ new Date(user.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button size="sm" variant="outline" as-child>
                                        <Link :href="`/admin/users/${user.id}/edit`">Edit</Link>
                                    </Button>
                                    <Button size="sm" variant="outline" @click="resetPassword(user.id)">
                                        Reset PW
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deleteUser(user.id)">
                                        Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
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
