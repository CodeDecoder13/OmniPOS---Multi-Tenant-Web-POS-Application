<script setup lang="ts">
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
import { useTenant } from '@/composables/useTenant';
import type {
    Branch,
    BranchComparisonItem,
    OperatorPerformanceItem,
    PaymentBreakdownItem,
    ReportFilters,
    ReportSummary,
    SalesTrend,
    TopProducts,
} from '@/types';

const props = defineProps<{
    filters: ReportFilters;
    summary: ReportSummary;
    salesTrend: SalesTrend;
    topProducts: TopProducts;
    paymentBreakdown: PaymentBreakdownItem[];
    operatorPerformance: OperatorPerformanceItem[];
    branchComparison: BranchComparisonItem[];
    branches: Pick<Branch, 'id' | 'name'>[];
}>();

const { tenantUrl } = useTenant();

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
        <div class="mx-auto max-w-7xl space-y-6 p-6">
            <h1 class="text-2xl font-bold tracking-tight">Reports</h1>

            <ReportFilterBar :filters="filters" :branches="branches" @apply="applyFilters" />

            <SummaryCards :summary="summary" />

            <Tabs default-value="overview">
                <TabsList>
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="products">Products</TabsTrigger>
                    <TabsTrigger value="payments">Payments</TabsTrigger>
                    <TabsTrigger value="team">Team</TabsTrigger>
                    <TabsTrigger v-if="branches.length > 1" value="branches">Branches</TabsTrigger>
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
            </Tabs>
        </div>
    </TenantLayout>
</template>
