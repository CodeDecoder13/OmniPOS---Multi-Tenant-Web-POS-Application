<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { AdminActivityLog, BreadcrumbItem, PaginatedData } from '@/types';
import { ref, watch } from 'vue';

const props = defineProps<{
    logs: PaginatedData<AdminActivityLog>;
    filters: { admin_id?: string; action?: string; subject_type?: string; date_from?: string; date_to?: string };
    admins: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Activity Log', href: '/admin/activity-log' },
];

const adminFilter = ref(props.filters.admin_id ?? '');
const actionFilter = ref(props.filters.action ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

function applyFilters() {
    router.get('/admin/activity-log', {
        admin_id: adminFilter.value || undefined,
        action: actionFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    adminFilter.value = '';
    actionFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    router.get('/admin/activity-log');
}

function formatAction(action: string): string {
    return action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

const expandedId = ref<number | null>(null);

function toggleExpand(id: number) {
    expandedId.value = expandedId.value === id ? null : id;
}
</script>

<template>
    <Head title="Activity Log" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">Activity Log</h1>

            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-3">
                <div class="grid gap-1">
                    <Label class="text-xs">Admin</Label>
                    <select
                        v-model="adminFilter"
                        class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        @change="applyFilters"
                    >
                        <option value="">All Admins</option>
                        <option v-for="admin in admins" :key="admin.id" :value="admin.id">{{ admin.name }}</option>
                    </select>
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">Action</Label>
                    <Input v-model="actionFilter" placeholder="Filter by action..." class="w-40" @keydown.enter="applyFilters" />
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">From</Label>
                    <Input v-model="dateFrom" type="date" class="w-40" />
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">To</Label>
                    <Input v-model="dateTo" type="date" class="w-40" />
                </div>
                <Button class="bg-teal-600 hover:bg-teal-700" @click="applyFilters">Apply</Button>
                <Button variant="outline" @click="clearFilters">Clear</Button>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Admin</th>
                            <th class="px-4 py-3 text-left font-medium">Action</th>
                            <th class="px-4 py-3 text-left font-medium">Subject</th>
                            <th class="px-4 py-3 text-left font-medium">IP Address</th>
                            <th class="px-4 py-3 text-left font-medium">Date</th>
                            <th class="px-4 py-3 text-right font-medium">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="log in logs.data" :key="log.id">
                            <tr class="border-b last:border-0 dark:border-gray-800">
                                <td class="px-4 py-3 font-medium">{{ log.admin?.name ?? 'System' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ formatAction(log.action) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ log.subject_type ? `${log.subject_type}#${log.subject_id}` : '—' }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground font-mono text-xs">{{ log.ip_address ?? '—' }}</td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ new Date(log.created_at).toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button v-if="log.properties" size="sm" variant="ghost" @click="toggleExpand(log.id)">
                                        {{ expandedId === log.id ? 'Hide' : 'Show' }}
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="expandedId === log.id && log.properties" class="border-b dark:border-gray-800">
                                <td colspan="6" class="bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
                                    <pre class="whitespace-pre-wrap text-xs text-muted-foreground">{{ JSON.stringify(log.properties, null, 2) }}</pre>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                No activity logs found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in logs.links" :key="link.label">
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
