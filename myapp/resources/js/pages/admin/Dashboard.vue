<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, DollarSign, TrendingDown, TrendingUp, UserPlus, Clock, BarChart3, Activity, LogIn, Eye, Fingerprint } from 'lucide-vue-next';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { AdminActivityLog, RecentUserLogin, BreadcrumbItem } from '@/types';
import { computed, ref } from 'vue';

const activeTab = ref('revenue');
const activityTab = ref('admin');

const props = defineProps<{
    stats: {
        total_tenants: number;
        active_tenants: number;
        total_users: number;
        mrr: number;
        trial_tenants: number;
        new_signups: number;
        churn_rate: number;
        avg_revenue: number;
    };
    revenueTrend: { labels: string[]; data: number[] };
    planDistribution: { name: string; count: number }[];
    recentActivity: AdminActivityLog[];
    recentUserLogins: RecentUserLogin[];
    userActivity: {
        logins_today: number;
        logins_week: number;
        logins_month: number;
        active_sessions: number;
    };
    loginTrend: { labels: string[]; data: number[] };
    pageVisits: {
        visits_today: number;
        visits_week: number;
        visits_month: number;
        unique_today: number;
    };
    pageVisitTrend: { labels: string[]; total: number[]; unique: number[] };
    topReferrers: { referrer: string; count: number }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin' },
];

const chartBase = {
    chart: { toolbar: { show: false }, fontFamily: 'inherit', sparkline: { enabled: false } },
    stroke: { curve: 'smooth' as const, width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
    dataLabels: { enabled: false },
    grid: { padding: { left: 4, right: 4, top: -10, bottom: 0 }, show: true, borderColor: '#f0f0f0' },
};

const revenueChartOptions = computed(() => ({
    ...chartBase,
    chart: { ...chartBase.chart, type: 'area' as const },
    xaxis: { categories: props.revenueTrend.labels, labels: { style: { fontSize: '10px' }, rotate: -45, rotateAlways: false }, axisBorder: { show: false }, tickAmount: 6 },
    yaxis: { labels: { formatter: (val: number) => `₱${val.toLocaleString()}`, style: { fontSize: '10px' } } },
    colors: ['#0d9488'],
    tooltip: { y: { formatter: (val: number) => `₱${val.toLocaleString('en-PH', { minimumFractionDigits: 2 })}` } },
}));

const revenueSeries = computed(() => ([{ name: 'Revenue', data: props.revenueTrend.data }]));

const loginTrendOptions = computed(() => ({
    ...chartBase,
    chart: { ...chartBase.chart, type: 'area' as const },
    xaxis: { categories: props.loginTrend.labels, labels: { style: { fontSize: '10px' }, rotate: -45, rotateAlways: false }, axisBorder: { show: false }, tickAmount: 6 },
    yaxis: { labels: { formatter: (val: number) => Math.round(val).toString(), style: { fontSize: '10px' } } },
    colors: ['#3b82f6'],
    tooltip: { y: { formatter: (val: number) => `${val} logins` } },
}));

const loginTrendSeries = computed(() => ([{ name: 'Logins', data: props.loginTrend.data }]));

const pageVisitTrendOptions = computed(() => ({
    ...chartBase,
    chart: { ...chartBase.chart, type: 'area' as const },
    xaxis: { categories: props.pageVisitTrend.labels, labels: { style: { fontSize: '10px' }, rotate: -45, rotateAlways: false }, axisBorder: { show: false }, tickAmount: 6 },
    yaxis: { labels: { formatter: (val: number) => Math.round(val).toString(), style: { fontSize: '10px' } } },
    colors: ['#8b5cf6', '#f59e0b'],
    tooltip: { y: { formatter: (val: number) => `${val} visits` } },
}));

const pageVisitTrendSeries = computed(() => ([
    { name: 'Total Visits', data: props.pageVisitTrend.total },
    { name: 'Unique Visitors', data: props.pageVisitTrend.unique },
]));

const planChartOptions = computed(() => ({
    chart: { type: 'donut' as const, fontFamily: 'inherit' },
    labels: props.planDistribution.map(p => p.name),
    colors: ['#0d9488', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
    legend: { position: 'bottom' as const, fontSize: '11px' },
    plotOptions: { pie: { donut: { size: '70%' } } },
}));

const planSeries = computed(() => props.planDistribution.map(p => p.count));

function formatAction(action: string): string {
    return action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h ago`;
    return `${Math.floor(hours / 24)}d ago`;
}
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-3 overflow-y-auto p-4">

            <!-- Row 1: Stat cards in 2 groups -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
                <!-- Business stats -->
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-teal-600 bg-teal-100 dark:bg-teal-900/30"><Building2 class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Total Tenants</p><p class="text-base font-bold leading-tight">{{ stats.total_tenants }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-green-600 bg-green-100 dark:bg-green-900/30"><TrendingUp class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Active Tenants</p><p class="text-base font-bold leading-tight">{{ stats.active_tenants }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-amber-600 bg-amber-100 dark:bg-amber-900/30"><DollarSign class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">MRR</p><p class="text-base font-bold leading-tight">₱{{ Number(stats.mrr).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-cyan-600 bg-cyan-100 dark:bg-cyan-900/30"><BarChart3 class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Avg Rev/Tenant</p><p class="text-base font-bold leading-tight">₱{{ Number(stats.avg_revenue).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-purple-600 bg-purple-100 dark:bg-purple-900/30"><UserPlus class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">New Signups</p><p class="text-base font-bold leading-tight">{{ stats.new_signups }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-indigo-600 bg-indigo-100 dark:bg-indigo-900/30"><Clock class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Trial Tenants</p><p class="text-base font-bold leading-tight">{{ stats.trial_tenants }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-red-600 bg-red-100 dark:bg-red-900/30"><TrendingDown class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Churn Rate</p><p class="text-base font-bold leading-tight">{{ stats.churn_rate }}%</p></div>
                    </div>
                </div>
            </div>

            <!-- Row 1b: Activity stats -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30"><Activity class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Active Sessions</p><p class="text-base font-bold leading-tight">{{ userActivity.active_sessions }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-blue-600 bg-blue-100 dark:bg-blue-900/30"><LogIn class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Logins Today</p><p class="text-base font-bold leading-tight">{{ userActivity.logins_today }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-violet-600 bg-violet-100 dark:bg-violet-900/30"><Eye class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Page Visits Today</p><p class="text-base font-bold leading-tight">{{ pageVisits.visits_today }}</p></div>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-lg p-1.5 text-orange-600 bg-orange-100 dark:bg-orange-900/30"><Fingerprint class="h-3.5 w-3.5" /></div>
                        <div><p class="text-[11px] text-muted-foreground">Unique Visitors</p><p class="text-base font-bold leading-tight">{{ pageVisits.unique_today }}</p></div>
                    </div>
                </div>
            </div>

            <!-- Row 2: Tabbed Chart + Plan Distribution -->
            <div class="grid gap-3 lg:grid-cols-12">
                <!-- Tabbed Trend Chart -->
                <div class="lg:col-span-8 flex flex-col rounded-xl border bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <Tabs v-model="activeTab" class="flex flex-1 flex-col">
                        <div class="mb-1 flex items-center justify-between">
                            <TabsList class="h-7">
                                <TabsTrigger value="revenue" class="text-xs px-2.5 py-0.5">Revenue</TabsTrigger>
                                <TabsTrigger value="logins" class="text-xs px-2.5 py-0.5">Logins</TabsTrigger>
                                <TabsTrigger value="visits" class="text-xs px-2.5 py-0.5">Page Visits</TabsTrigger>
                            </TabsList>
                            <Link v-if="activeTab === 'revenue'" href="/admin/reports/revenue" class="text-xs text-teal-600 hover:underline">View Report</Link>
                        </div>
                        <TabsContent value="revenue" class="mt-0 flex-1">
                            <apexchart type="area" height="170" :options="revenueChartOptions" :series="revenueSeries" />
                        </TabsContent>
                        <TabsContent value="logins" class="mt-0 flex-1">
                            <apexchart type="area" height="170" :options="loginTrendOptions" :series="loginTrendSeries" />
                        </TabsContent>
                        <TabsContent value="visits" class="mt-0 flex-1">
                            <apexchart type="area" height="170" :options="pageVisitTrendOptions" :series="pageVisitTrendSeries" />
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- Plan Distribution -->
                <div class="lg:col-span-4 rounded-xl border bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-1 flex items-center justify-between">
                        <h2 class="text-sm font-semibold">Plan Distribution</h2>
                        <Link href="/admin/reports/subscriptions" class="text-xs text-teal-600 hover:underline">Report</Link>
                    </div>
                    <apexchart v-if="planSeries.some(v => v > 0)" type="donut" height="185" :options="planChartOptions" :series="planSeries" />
                    <div v-else class="flex h-[185px] items-center justify-center text-xs text-muted-foreground">No subscription data</div>
                </div>
            </div>

            <!-- Row 3: Recent Activity + Quick Actions -->
            <div class="grid gap-3 lg:grid-cols-12">
                <!-- Recent Activity -->
                <div class="lg:col-span-8 rounded-xl border bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <Tabs v-model="activityTab">
                        <div class="mb-2 flex items-center justify-between">
                            <TabsList class="h-7">
                                <TabsTrigger value="admin" class="text-xs px-2.5 py-0.5">Admin Activity</TabsTrigger>
                                <TabsTrigger value="logins" class="text-xs px-2.5 py-0.5">User Logins</TabsTrigger>
                            </TabsList>
                            <Link v-if="activityTab === 'admin'" href="/admin/activity-log" class="text-xs text-teal-600 hover:underline">View All</Link>
                        </div>
                        <TabsContent value="admin" class="mt-0">
                            <div v-if="recentActivity.length > 0" class="space-y-1.5">
                                <div
                                    v-for="log in recentActivity.slice(0, 5)"
                                    :key="log.id"
                                    class="flex items-center justify-between rounded-lg border px-3 py-1.5 dark:border-gray-800"
                                >
                                    <div class="text-xs">
                                        <span class="font-medium">{{ log.admin?.name ?? 'System' }}</span>
                                        <span class="text-muted-foreground"> {{ formatAction(log.action) }}</span>
                                    </div>
                                    <span class="text-[11px] text-muted-foreground">{{ timeAgo(log.created_at) }}</span>
                                </div>
                            </div>
                            <p v-else class="text-xs text-muted-foreground">No recent activity.</p>
                        </TabsContent>
                        <TabsContent value="logins" class="mt-0">
                            <div v-if="recentUserLogins.length > 0" class="space-y-1.5">
                                <div
                                    v-for="login in recentUserLogins.slice(0, 5)"
                                    :key="login.id"
                                    class="flex items-center justify-between rounded-lg border px-3 py-1.5 dark:border-gray-800"
                                >
                                    <div class="text-xs">
                                        <span class="font-medium">{{ login.user?.name ?? 'Unknown' }}</span>
                                        <span class="text-muted-foreground"> logged in</span>
                                        <span v-if="login.tenant" class="text-muted-foreground"> to <span class="font-medium text-foreground">{{ login.tenant.name }}</span></span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] text-muted-foreground">
                                        <span v-if="login.ip_address">{{ login.ip_address }}</span>
                                        <span>{{ timeAgo(login.logged_in_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-xs text-muted-foreground">No recent logins.</p>
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- Quick Actions -->
                <div class="lg:col-span-4 rounded-xl border bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-2 text-sm font-semibold">Quick Actions</h2>
                    <div class="grid grid-cols-2 gap-1.5">
                        <Link href="/admin/tenants/create" class="rounded-lg border px-3 py-1.5 text-xs font-medium hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800">+ New Tenant</Link>
                        <Link href="/admin/users/create" class="rounded-lg border px-3 py-1.5 text-xs font-medium hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800">+ New User</Link>
                        <Link href="/admin/plans/create" class="rounded-lg border px-3 py-1.5 text-xs font-medium hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800">+ New Plan</Link>
                        <Link href="/admin/promo-codes/create" class="rounded-lg border px-3 py-1.5 text-xs font-medium hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800">+ Promo Code</Link>
                        <Link href="/admin/reports/revenue" class="col-span-2 rounded-lg border px-3 py-1.5 text-center text-xs font-medium hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800">View Reports</Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
