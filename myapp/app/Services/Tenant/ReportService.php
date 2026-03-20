<?php

namespace App\Services\Tenant;

use App\Enums\AdjustmentType;
use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Payment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getSummary(Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, ?int $branchId): array
    {
        $stats = $this->baseDbQuery($tenant, $dateFrom, $dateTo, $branchId)
            ->selectRaw('
                COALESCE(SUM(total), 0) as total_revenue,
                COUNT(*) as order_count,
                COALESCE(AVG(total), 0) as avg_order_value,
                COALESCE(SUM(discount_amount), 0) as total_discounts
            ')->first();

        $itemsSold = OrderItem::whereIn('order_id', function ($q) use ($tenant, $dateFrom, $dateTo, $branchId) {
            $q->select('id')->from('orders')
                ->where('tenant_id', $tenant->id)
                ->where('status', OrderStatus::Completed->value)
                ->whereBetween('created_at', [$dateFrom, $dateTo]);

            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->sum('quantity');

        return [
            'total_revenue' => round((float) $stats->total_revenue, 2),
            'order_count' => (int) $stats->order_count,
            'avg_order_value' => round((float) $stats->avg_order_value, 2),
            'items_sold' => (int) $itemsSold,
            'total_discounts' => round((float) $stats->total_discounts, 2),
        ];
    }

    public function getSalesTrend(Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, string $period, ?int $branchId): array
    {
        $truncFormat = match ($period) {
            'weekly' => 'week',
            'monthly' => 'month',
            default => 'day',
        };

        $results = $this->baseDbQuery($tenant, $dateFrom, $dateTo, $branchId)
            ->selectRaw("date_trunc('{$truncFormat}', created_at) as period_date")
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->selectRaw('COUNT(*) as order_count')
            ->groupByRaw("date_trunc('{$truncFormat}', created_at)")
            ->orderBy('period_date')
            ->get();

        $dateFormat = match ($period) {
            'weekly' => 'M d',
            'monthly' => 'M Y',
            default => 'M d',
        };

        return [
            'labels' => $results->pluck('period_date')->map(fn ($d) => Carbon::parse($d)->format($dateFormat))->toArray(),
            'revenue' => $results->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->toArray(),
            'order_count' => $results->pluck('order_count')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    public function getTopProducts(Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, ?int $branchId): array
    {
        $orderIds = $this->baseQuery($tenant, $dateFrom, $dateTo, $branchId)->pluck('id');

        $baseQuery = OrderItem::whereIn('order_id', $orderIds)
            ->select('product_name')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(subtotal) as total_revenue')
            ->groupBy('product_name');

        $byQuantity = (clone $baseQuery)->orderByDesc('total_quantity')->limit(10)->get()
            ->map(fn ($row) => [
                'product_name' => $row->product_name,
                'total_quantity' => (int) $row->total_quantity,
                'total_revenue' => round((float) $row->total_revenue, 2),
            ])->toArray();

        $byRevenue = (clone $baseQuery)->orderByDesc('total_revenue')->limit(10)->get()
            ->map(fn ($row) => [
                'product_name' => $row->product_name,
                'total_quantity' => (int) $row->total_quantity,
                'total_revenue' => round((float) $row->total_revenue, 2),
            ])->toArray();

        return [
            'by_quantity' => $byQuantity,
            'by_revenue' => $byRevenue,
        ];
    }

    public function getPaymentBreakdown(Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, ?int $branchId): array
    {
        $orderIds = $this->baseQuery($tenant, $dateFrom, $dateTo, $branchId)->pluck('id');

        return Payment::whereIn('order_id', $orderIds)
            ->select('method')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('method')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method->value,
                'label' => $row->method->label(),
                'count' => (int) $row->count,
                'total_amount' => round((float) $row->total_amount, 2),
            ])->toArray();
    }

    public function getOperatorPerformance(Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, ?int $branchId): array
    {
        $results = $this->baseDbQuery($tenant, $dateFrom, $dateTo, $branchId)
            ->select('created_by')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->selectRaw('COALESCE(AVG(total), 0) as avg_order_value')
            ->whereNotNull('created_by')
            ->groupBy('created_by')
            ->orderByDesc('total_revenue')
            ->get();

        $userIds = $results->pluck('created_by')->unique()->filter();
        $users = User::whereIn('id', $userIds)->pluck('name', 'id');

        return $results->map(fn ($row) => [
            'user_id' => (int) $row->created_by,
            'user_name' => $users[$row->created_by] ?? 'Unknown',
            'order_count' => (int) $row->order_count,
            'total_revenue' => round((float) $row->total_revenue, 2),
            'avg_order_value' => round((float) $row->avg_order_value, 2),
        ])->toArray();
    }

    public function getBranchComparison(Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo): array
    {
        return DB::table('orders')
            ->where('orders.tenant_id', $tenant->id)
            ->where('orders.status', OrderStatus::Completed->value)
            ->whereBetween('orders.created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ])
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->select('orders.branch_id', 'branches.name as branch_name')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(orders.total), 0) as total_revenue')
            ->selectRaw('COALESCE(AVG(orders.total), 0) as avg_order_value')
            ->groupBy('orders.branch_id', 'branches.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn ($row) => [
                'branch_id' => (int) $row->branch_id,
                'branch_name' => $row->branch_name,
                'order_count' => (int) $row->order_count,
                'total_revenue' => round((float) $row->total_revenue, 2),
                'avg_order_value' => round((float) $row->avg_order_value, 2),
            ])->toArray();
    }

    public function getInventoryReport(Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, ?int $branchId): array
    {
        $stockQuery = DB::table('inventory')
            ->where('inventory.tenant_id', $tenant->id)
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->join('branches', 'inventory.branch_id', '=', 'branches.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'inventory.product_id',
                'products.name as product_name',
                'categories.name as category_name',
                'branches.name as branch_name',
                'inventory.quantity_on_hand',
                'inventory.low_stock_threshold',
                DB::raw('COALESCE(products.cost_price, 0) as cost_price'),
                'products.price',
                DB::raw('COALESCE(products.cost_price, 0) * inventory.quantity_on_hand as stock_value'),
            );

        if ($branchId) {
            $stockQuery->where('inventory.branch_id', $branchId);
        }

        $stockLevels = $stockQuery->orderBy('products.name')->get()->map(fn ($row) => [
            'product_id' => (int) $row->product_id,
            'product_name' => $row->product_name,
            'category_name' => $row->category_name ?? 'Uncategorized',
            'branch_name' => $row->branch_name,
            'quantity_on_hand' => (int) $row->quantity_on_hand,
            'low_stock_threshold' => (int) $row->low_stock_threshold,
            'cost_price' => round((float) $row->cost_price, 2),
            'price' => round((float) $row->price, 2),
            'stock_value' => round((float) $row->stock_value, 2),
            'is_low_stock' => $row->low_stock_threshold > 0 && $row->quantity_on_hand <= $row->low_stock_threshold,
        ])->toArray();

        $lowStockItems = array_values(array_filter($stockLevels, fn ($item) => $item['is_low_stock']));

        // Stock movement summary
        $movementQuery = DB::table('inventory_adjustments')
            ->join('inventory', 'inventory_adjustments.inventory_id', '=', 'inventory.id')
            ->where('inventory.tenant_id', $tenant->id)
            ->whereBetween('inventory_adjustments.created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ])
            ->select('inventory_adjustments.type')
            ->selectRaw('SUM(ABS(inventory_adjustments.quantity_change)) as total_quantity')
            ->groupBy('inventory_adjustments.type');

        if ($branchId) {
            $movementQuery->where('inventory.branch_id', $branchId);
        }

        $stockMovement = $movementQuery->get()->map(fn ($row) => [
            'type' => $row->type,
            'label' => AdjustmentType::from($row->type)->label(),
            'total_quantity' => (int) $row->total_quantity,
        ])->toArray();

        // Branch valuations
        $valuationQuery = DB::table('inventory')
            ->where('inventory.tenant_id', $tenant->id)
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->join('branches', 'inventory.branch_id', '=', 'branches.id')
            ->select('branches.name as branch_name')
            ->selectRaw('COALESCE(SUM(COALESCE(products.cost_price, 0) * inventory.quantity_on_hand), 0) as total_value')
            ->selectRaw('COUNT(*) as item_count')
            ->groupBy('branches.name')
            ->orderByDesc('total_value');

        if ($branchId) {
            $valuationQuery->where('inventory.branch_id', $branchId);
        }

        $branchValuations = $valuationQuery->get()->map(fn ($row) => [
            'branch_name' => $row->branch_name,
            'total_value' => round((float) $row->total_value, 2),
            'item_count' => (int) $row->item_count,
        ])->toArray();

        $totalStockValue = array_sum(array_column($branchValuations, 'total_value'));

        return [
            'stock_levels' => $stockLevels,
            'low_stock_items' => $lowStockItems,
            'stock_movement' => $stockMovement,
            'total_stock_value' => round($totalStockValue, 2),
            'branch_valuations' => $branchValuations,
        ];
    }

    public function getTaxReport(Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, string $period, ?int $branchId): array
    {
        $truncFormat = match ($period) {
            'weekly' => 'week',
            'monthly' => 'month',
            default => 'day',
        };

        $dateFormat = match ($period) {
            'weekly' => 'M d',
            'monthly' => 'M Y',
            default => 'M d',
        };

        // Tax by period
        $byPeriod = $this->baseDbQuery($tenant, $dateFrom, $dateTo, $branchId)
            ->selectRaw("date_trunc('{$truncFormat}', created_at) as period_date")
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(subtotal - discount_amount - promotion_discount), 0) as taxable_amount')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as tax_amount')
            ->groupByRaw("date_trunc('{$truncFormat}', created_at)")
            ->orderBy('period_date')
            ->get()
            ->map(fn ($row) => [
                'period' => Carbon::parse($row->period_date)->format($dateFormat),
                'order_count' => (int) $row->order_count,
                'taxable_amount' => round((float) $row->taxable_amount, 2),
                'tax_amount' => round((float) $row->tax_amount, 2),
            ])->toArray();

        // Tax by order type
        $byOrderType = $this->baseDbQuery($tenant, $dateFrom, $dateTo, $branchId)
            ->select('order_type')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as tax_amount')
            ->groupBy('order_type')
            ->orderByDesc('tax_amount')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->order_type,
                'label' => str_replace('_', ' ', ucfirst($row->order_type)),
                'tax_amount' => round((float) $row->tax_amount, 2),
                'order_count' => (int) $row->order_count,
            ])->toArray();

        // Totals
        $totals = $this->baseDbQuery($tenant, $dateFrom, $dateTo, $branchId)
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as total_tax')
            ->selectRaw('COALESCE(SUM(subtotal - discount_amount - promotion_discount), 0) as total_taxable')
            ->first();

        $totalTax = round((float) $totals->total_tax, 2);
        $totalTaxable = round((float) $totals->total_taxable, 2);
        $effectiveRate = $totalTaxable > 0 ? round(($totalTax / $totalTaxable) * 100, 2) : 0;

        $tenantData = $tenant->data ?? [];

        return [
            'total_tax' => $totalTax,
            'total_taxable' => $totalTaxable,
            'effective_rate' => $effectiveRate,
            'tax_inclusive' => (bool) ($tenantData['tax_inclusive'] ?? false),
            'tax_rate' => (float) ($tenantData['tax_rate'] ?? 0),
            'by_period' => $byPeriod,
            'by_order_type' => $byOrderType,
        ];
    }

    public function getForecast(Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, ?int $branchId): array
    {
        // Get daily revenue for historical data
        $dailyRevenue = $this->baseDbQuery($tenant, $dateFrom, $dateTo, $branchId)
            ->selectRaw("date_trunc('day', created_at) as period_date")
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->groupByRaw("date_trunc('day', created_at)")
            ->orderBy('period_date')
            ->get();

        $labels = $dailyRevenue->pluck('period_date')->map(fn ($d) => Carbon::parse($d)->format('M d'))->toArray();
        $revenue = $dailyRevenue->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->toArray();

        $count = count($revenue);

        // Moving averages
        $ma7 = $this->movingAverage($revenue, 7);
        $ma30 = $this->movingAverage($revenue, 30);

        // Growth rate: compare second half vs first half of period
        $midpoint = intdiv($count, 2);
        $firstHalf = $midpoint > 0 ? array_sum(array_slice($revenue, 0, $midpoint)) : 0;
        $secondHalf = $midpoint > 0 ? array_sum(array_slice($revenue, $midpoint)) : 0;
        $growthRate = $firstHalf > 0 ? round((($secondHalf - $firstHalf) / $firstHalf) * 100, 2) : 0;

        // Linear trend extrapolation for projections
        $projectedLabels = [];
        $projectedRevenue = [];
        if ($count >= 2) {
            $slope = $this->linearSlope($revenue);
            $lastVal = end($revenue) ?: 0;
            $lastDate = $count > 0 ? Carbon::parse($dailyRevenue->last()->period_date) : Carbon::parse($dateTo);

            for ($i = 1; $i <= 30; $i++) {
                $projectedLabels[] = $lastDate->copy()->addDays($i)->format('M d');
                $projectedRevenue[] = round(max(0, $lastVal + $slope * $i), 2);
            }
        }

        // Projected revenue sums
        $proj7 = round(array_sum(array_slice($projectedRevenue, 0, 7)), 2);
        $proj14 = round(array_sum(array_slice($projectedRevenue, 0, 14)), 2);
        $proj30 = round(array_sum($projectedRevenue), 2);

        // Day of week pattern
        $dowPattern = [];
        $dowSums = array_fill(0, 7, 0);
        $dowCounts = array_fill(0, 7, 0);

        foreach ($dailyRevenue as $row) {
            $dow = Carbon::parse($row->period_date)->dayOfWeek; // 0=Sun, 6=Sat
            $dowSums[$dow] += (float) $row->revenue;
            $dowCounts[$dow]++;
        }

        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        for ($i = 0; $i < 7; $i++) {
            $dowPattern[] = [
                'day' => $dayNames[$i],
                'avg_revenue' => $dowCounts[$i] > 0 ? round($dowSums[$i] / $dowCounts[$i], 2) : 0,
            ];
        }

        return [
            'historical' => ['labels' => $labels, 'revenue' => $revenue],
            'projected' => ['labels' => $projectedLabels, 'revenue' => $projectedRevenue],
            'moving_avg_7' => $ma7,
            'moving_avg_30' => $ma30,
            'growth_rate' => $growthRate,
            'projected_revenue_7d' => $proj7,
            'projected_revenue_14d' => $proj14,
            'projected_revenue_30d' => $proj30,
            'day_of_week_pattern' => $dowPattern,
        ];
    }

    private function movingAverage(array $data, int $window): array
    {
        $result = [];
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {
            if ($i < $window - 1) {
                $result[] = null;
            } else {
                $slice = array_slice($data, $i - $window + 1, $window);
                $result[] = round(array_sum($slice) / $window, 2);
            }
        }
        return $result;
    }

    private function linearSlope(array $data): float
    {
        $n = count($data);
        if ($n < 2) return 0;

        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $data[$i];
            $sumXY += $i * $data[$i];
            $sumX2 += $i * $i;
        }

        $denom = ($n * $sumX2 - $sumX * $sumX);
        return $denom != 0 ? ($n * $sumXY - $sumX * $sumY) / $denom : 0;
    }

    private function baseQuery(Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, ?int $branchId): \Illuminate\Database\Eloquent\Builder
    {
        $query = Order::where('tenant_id', $tenant->id)
            ->where('status', OrderStatus::Completed->value)
            ->whereBetween('created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    private function baseDbQuery(Tenant $tenant, CarbonInterface $dateFrom, CarbonInterface $dateTo, ?int $branchId): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('orders')
            ->where('tenant_id', $tenant->id)
            ->where('status', OrderStatus::Completed->value)
            ->whereBetween('created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }
}
