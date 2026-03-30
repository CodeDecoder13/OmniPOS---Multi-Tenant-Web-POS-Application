<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, PaginatedData, ReleaseNote } from '@/types';
import { Plus } from 'lucide-vue-next';

defineProps<{
    releaseNotes: PaginatedData<ReleaseNote>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Release Notes', href: '/admin/release-notes' },
];

function deleteReleaseNote(id: number) {
    if (!confirm('Are you sure you want to delete this release note?')) return;
    router.delete(`/admin/release-notes/${id}`);
}

function formatDate(date: string | null): string {
    if (!date) return '—';
    return new Date(date).toLocaleDateString();
}
</script>

<template>
    <Head title="Release Notes" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Release Notes</h1>
                <Button as-child class="bg-teal-600 hover:bg-teal-700">
                    <Link href="/admin/release-notes/create">
                        <Plus class="mr-1 size-4" />
                        Create Release Note
                    </Link>
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Title</th>
                            <th class="px-4 py-3 text-left font-medium">Version</th>
                            <th class="px-4 py-3 text-left font-medium">Items</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Published At</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="note in releaseNotes.data"
                            :key="note.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3">
                                <span class="font-semibold">{{ note.title }}</span>
                                <div v-if="note.summary" class="text-xs text-muted-foreground">{{ note.summary }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium dark:bg-gray-800">
                                    v{{ note.version }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ note.items.length }} item{{ note.items.length !== 1 ? 's' : '' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="note.is_published
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'"
                                >
                                    {{ note.is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                {{ formatDate(note.published_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button size="sm" variant="outline" as-child>
                                        <Link :href="`/admin/release-notes/${note.id}/edit`">Edit</Link>
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deleteReleaseNote(note.id)">
                                        Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="releaseNotes.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                No release notes found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="releaseNotes.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in releaseNotes.links" :key="link.label">
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
