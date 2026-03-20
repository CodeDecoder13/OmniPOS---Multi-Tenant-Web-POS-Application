<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import ReportFilterBar from '@/components/reports/ReportFilterBar.vue';
import SummaryCards from '@/components/reports/SummaryCards.vue';
import SalesTrendChart from '@/components/reports/SalesTrendChart.vue';
import TopProductsChart from '@/components/reports/TopProductsChart.vue';
import PaymentBreakdownChart from '@/components/reports/PaymentBreakdownChart.vue';
import OperatorTable from '@/components/reports/OperatorTable.vue';
import BranchComparisonChart from '@/components/reports/BranchComparisonChart.vue';
import InventoryReportChart from '@/components/reports/InventoryReportChart.vue';
import TaxReportChart from '@/components/reports/TaxReportChart.vue';
import ForecastChart from '@/components/reports/ForecastChart.vue';
import ExportButton from '@/components/reports/ExportButton.vue';
import { useTenant } from '@/composables/useTenant';
import { BarChart3, Package, CreditCard, Users, GitBranch, Warehouse, Receipt, TrendingUp } from 'lucide-vue-next';
import type {
    Branch,
    BranchComparisonItem,
    OperatorPerformanceItem,
    PaymentBreakdownItem,
    ReportFilters,
    ReportSummary,
    SalesTrend,
    TopProducts,
    InventoryReport,
    TaxReport,
    ForecastData,
} from '@/types';

const props = defineProps<{
    filters: ReportFilters;
    summary: ReportSummary;
    salesTrend: SalesTrend;
    topProducts: TopProducts;
    paymentBreakdown: PaymentBreakdownItem[];
    operatorPerformance: OperatorPerformanceItem[];
    branchComparison: BranchComparisonItem[];
    inventoryReport: InventoryReport;
    taxReport: TaxReport;
    forecast: ForecastData;
    branches: Pick<Branch, 'id' | 'name'>[];
}>();

const { tenantUrl } = useTenant();

const activeTab = ref('overview');

const exportTypeMap: Record<string, string> = {
    overview: 'sales_summary',
    products: 'products',
    payments: 'payments',
    team: 'operators',
    branches: 'branches',
    inventory: 'inventory',
    tax: 'tax',
};

function applyFilters(filters: { date_from: string; date_to: string; period: string; branch_id: number | null }) {
    router.get(tenantUrl('reports'), {
        date_from: filters.date_from,
        date_to: filters.date_to,
        period: filters.period,
        branch_id: filters.branch_id ?? undefined,
    }, { preserveState: true, replace: true });
}

const breadcrumbs = [{ title: 'Reports', href: tenantUrl('reports') }];
</script>

<template>
    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white shadow-lg shadow-teal-500/20">
                        <BarChart3 class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Reports</h1>
                        <p class="text-sm text-muted-foreground">Analytics and insights for your business</p>
                    </div>
                </div>
                <ExportButton :type="exportTypeMap[activeTab] ?? 'sales_summary'" :filters="filters" />
            </div>

            <ReportFilterBar :filters="filters" :branches="branches" @apply="applyFilters" />

            <SummaryCards :summary="summary" />

            <Tabs v-model="activeTab" default-value="overview">
                <TabsList class="h-auto flex-wrap gap-1 rounded-xl bg-muted/60 p-1 backdrop-blur">
                    <TabsTrigger value="overview" class="gap-2 rounded-lg px-4 py-2.5 text-sm data-[state=active]:bg-background data-[state=active]:shadow-sm">
                        <BarChart3 class="h-4 w-4" />
                        Overview
                    </TabsTrigger>
                    <TabsTrigger value="products" class="gap-2 rounded-lg px-4 py-2.5 text-sm data-[state=active]:bg-background data-[state=active]:shadow-sm">
                        <Package class="h-4 w-4" />
                        Products
                    </TabsTrigger>
                    <TabsTrigger value="payments" class="gap-2 rounded-lg px-4 py-2.5 text-sm data-[state=active]:bg-background data-[state=active]:shadow-sm">
                        <CreditCard class="h-4 w-4" />
                        Payments
                    </TabsTrigger>
                    <TabsTrigger value="team" class="gap-2 rounded-lg px-4 py-2.5 text-sm data-[state=active]:bg-background data-[state=active]:shadow-sm">
                        <Users class="h-4 w-4" />
                        Team
                    </TabsTrigger>
                    <TabsTrigger v-if="branches.length > 1" value="branches" class="gap-2 rounded-lg px-4 py-2.5 text-sm data-[state=active]:bg-background data-[state=active]:shadow-sm">
                        <GitBranch class="h-4 w-4" />
                        Branches
                    </TabsTrigger>
                    <TabsTrigger value="inventory" class="gap-2 rounded-lg px-4 py-2.5 text-sm data-[state=active]:bg-background data-[state=active]:shadow-sm">
                        <Warehouse class="h-4 w-4" />
                        Inventory
                    </TabsTrigger>
                    <TabsTrigger value="tax" class="gap-2 rounded-lg px-4 py-2.5 text-sm data-[state=active]:bg-background data-[state=active]:shadow-sm">
                        <Receipt class="h-4 w-4" />
                        Tax
                    </TabsTrigger>
                    <TabsTrigger value="forecast" class="gap-2 rounded-lg px-4 py-2.5 text-sm data-[state=active]:bg-background data-[state=active]:shadow-sm">
                        <TrendingUp class="h-4 w-4" />
                        Forecast
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="overview" class="mt-4">
                    <SalesTrendChart :data="salesTrend" />
                </TabsContent>

                <TabsContent value="products" class="mt-4">
                    <TopProductsChart :data="topProducts" />
                </TabsContent>

                <TabsContent value="payments" class="mt-4">
                    <PaymentBreakdownChart :data="paymentBreakdown" />
                </TabsContent>

                <TabsContent value="team" class="mt-4">
                    <OperatorTable :data="operatorPerformance" />
                </TabsContent>

                <TabsContent v-if="branches.length > 1" value="branches" class="mt-4">
                    <BranchComparisonChart :data="branchComparison" />
                </TabsContent>

                <TabsContent value="inventory" class="mt-4">
                    <InventoryReportChart :data="inventoryReport" />
                </TabsContent>

                <TabsContent value="tax" class="mt-4">
                    <TaxReportChart :data="taxReport" />
                </TabsContent>

                <TabsContent value="forecast" class="mt-4">
                    <ForecastChart :data="forecast" />
                </TabsContent>
            </Tabs>
        </div>
    </TenantLayout>
</template>
