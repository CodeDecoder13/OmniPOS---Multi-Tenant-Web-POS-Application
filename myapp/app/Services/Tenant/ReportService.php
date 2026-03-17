<?php

namespace App\Services\Tenant;

use App\Enums\OrderStatus;
use App\Models\Tenant;
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
