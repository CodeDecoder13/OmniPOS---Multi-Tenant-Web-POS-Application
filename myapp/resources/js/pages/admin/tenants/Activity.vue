<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { LogIn, Activity, ShoppingCart, Clock } from 'lucide-vue-next';
import type { BreadcrumbItem, PaginatedData, Tenant, TenantActivityEvent, TenantActivityStats } from '@/types';
import { ref } from 'vue';

const props = defineProps<{
    tenant: Tenant;
    stats: TenantActivityStats;
    timeline: PaginatedData<TenantActivityEvent>;
    filters: { user_id?: string; event_type?: string; date_from?: string; date_to?: string };
    users: { id: number; name: string }[];
    eventTypes: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
    { title: 'Tenants', href: '/admin/tenants' },
    { title: props.tenant.name, href: `/admin/tenants/${props.tenant.id}` },
    { title: 'User Activity', href: `/admin/tenants/${props.tenant.id}/activity` },
];

const userFilter = ref(props.filters.user_id ?? '');
const eventTypeFilter = ref(props.filters.event_type ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

function applyFilters() {
    router.get(`/admin/tenants/${props.tenant.id}/activity`, {
        user_id: userFilter.value || undefined,
        event_type: eventTypeFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    userFilter.value = '';
    eventTypeFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    router.get(`/admin/tenants/${props.tenant.id}/activity`);
}

const badgeClasses: Record<string, string> = {
    login: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    activity: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    shift_open: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    shift_close: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    order: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    product_created: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
};

const badgeLabels: Record<string, string> = {
    login: 'Login',
    activity: 'Activity',
    shift_open: 'Shift Open',
    shift_close: 'Shift Close',
    order: 'Order',
    product_created: 'Product Created',
};

const expandedIndex = ref<number | null>(null);

function toggleExpand(index: number) {
    expandedIndex.value = expandedIndex.value === index ? null : index;
}

function formatTime(dateStr: string): string {
    return new Date(dateStr).toLocaleString();
}
</script>

<template>
    <Head :title="`User Activity - ${tenant.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">User Activity</h1>
                    <p class="text-muted-foreground">{{ tenant.name }}</p>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="`/admin/tenants/${tenant.id}`">Back to Tenant</Link>
                </Button>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-blue-600 bg-blue-100 dark:bg-blue-900/30"><LogIn class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Logins Today</p><p class="text-base font-bold leading-tight">{{ stats.logins_today }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-green-600 bg-green-100 dark:bg-green-900/30"><Activity class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Active Users 24h</p><p class="text-base font-bold leading-tight">{{ stats.active_users_24h }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-teal-600 bg-teal-100 dark:bg-teal-900/30"><ShoppingCart class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Orders Today</p><p class="text-base font-bold leading-tight">{{ stats.orders_today }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-orange-600 bg-orange-100 dark:bg-orange-900/30"><Clock class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Open Shifts</p><p class="text-base font-bold leading-tight">{{ stats.open_shifts }}</p></div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-3">
                <div class="grid gap-1">
                    <Label class="text-xs">User</Label>
                    <select
                        v-model="userFilter"
                        class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        @change="applyFilters"
                    >
                        <option value="">All Users</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                    </select>
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">Event Type</Label>
                    <select
                        v-model="eventTypeFilter"
                        class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        @change="applyFilters"
                    >
                        <option value="">All Events</option>
                        <option v-for="type in eventTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
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

            <!-- Timeline Table -->
            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">User</th>
                            <th class="px-4 py-3 text-left font-medium">Event Type</th>
                            <th class="px-4 py-3 text-left font-medium">Description</th>
                            <th class="px-4 py-3 text-left font-medium">Time</th>
                            <th class="px-4 py-3 text-right font-medium">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(event, index) in timeline.data" :key="`${event.event_type}-${event.source_id}`">
                            <tr class="border-b last:border-0 dark:border-gray-800">
                                <td class="px-4 py-3 font-medium">{{ event.user_name }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="badgeClasses[event.event_type] ?? 'bg-gray-100 text-gray-700'"
                                    >
                                        {{ badgeLabels[event.event_type] ?? event.event_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ event.description }}</td>
                                <td class="px-4 py-3 text-muted-foreground whitespace-nowrap">{{ formatTime(event.occurred_at) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Button v-if="event.properties" size="sm" variant="ghost" @click="toggleExpand(index)">
                                        {{ expandedIndex === index ? 'Hide' : 'Show' }}
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="expandedIndex === index && event.properties" class="border-b dark:border-gray-800">
                                <td colspan="5" class="bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
                                    <pre class="whitespace-pre-wrap text-xs text-muted-foreground">{{ JSON.stringify(event.properties, null, 2) }}</pre>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="timeline.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                No activity found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="timeline.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in timeline.links" :key="link.label">
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
