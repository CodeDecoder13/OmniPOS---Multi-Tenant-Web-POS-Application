<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Admin, BreadcrumbItem, PaginatedData } from '@/types';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    admins: PaginatedData<Admin>;
    filters: { search?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Admins', href: '/admin/admins' },
];

const search = ref(props.filters.search ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get('/admin/admins', {
            search: search.value || undefined,
        }, { preserveState: true, replace: true });
    }, 300);
});

function deleteAdmin(id: number) {
    if (!confirm('Are you sure you want to delete this admin?')) return;
    router.delete(`/admin/admins/${id}`);
}
</script>

<template>
    <Head title="Admin Users" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Admin Users</h1>
                <Button as-child class="bg-teal-600 hover:bg-teal-700">
                    <Link href="/admin/admins/create">
                        <Plus class="mr-1 size-4" />
                        Create Admin
                    </Link>
                </Button>
            </div>

            <div class="flex items-center gap-3">
                <Input v-model="search" placeholder="Search admins..." class="w-64" />
            </div>

            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Email</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Last Login</th>
                            <th class="px-4 py-3 text-left font-medium">Created</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="admin in admins.data"
                            :key="admin.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3 font-medium">{{ admin.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ admin.email }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="admin.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                >
                                    {{ admin.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ admin.last_login_at ? new Date(admin.last_login_at).toLocaleString() : 'Never' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ new Date(admin.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button size="sm" variant="outline" as-child>
                                        <Link :href="`/admin/admins/${admin.id}/edit`">Edit</Link>
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deleteAdmin(admin.id)">
                                        Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="admins.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                No admins found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="admins.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in admins.links" :key="link.label">
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
