<?php

namespace App\Services\Tenant;

use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\Tenant\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AIInsightsService
{
    public function getSummary(Tenant $tenant): array
    {
        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $thisMonth = $this->periodStats($tenant, $thisMonthStart, $now);
        $lastMonth = $this->periodStats($tenant, $lastMonthStart, $lastMonthEnd);

        $revenueChange = $lastMonth['revenue'] > 0
            ? round((($thisMonth['revenue'] - $lastMonth['revenue']) / $lastMonth['revenue']) * 100, 1)
            : ($thisMonth['revenue'] > 0 ? 100 : 0);

        $orderChange = $lastMonth['orders'] > 0
            ? round((($thisMonth['orders'] - $lastMonth['orders']) / $lastMonth['orders']) * 100, 1)
            : ($thisMonth['orders'] > 0 ? 100 : 0);

        $thisAvg = $thisMonth['orders'] > 0 ? $thisMonth['revenue'] / $thisMonth['orders'] : 0;
        $lastAvg = $lastMonth['orders'] > 0 ? $lastMonth['revenue'] / $lastMonth['orders'] : 0;
        $avgOrderChange = $lastAvg > 0
            ? round((($thisAvg - $lastAvg) / $lastAvg) * 100, 1)
            : ($thisAvg > 0 ? 100 : 0);

        // 7-day projection using last 14 days trend
        $last14 = $this->baseDbQuery($tenant, $now->copy()->subDays(13), $now)
            ->selectRaw("date_trunc('day', created_at) as d")
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->groupByRaw("date_trunc('day', created_at)")
            ->orderBy('d')
            ->get();

        $dailyRevenues = $last14->pluck('revenue')->map(fn ($v) => (float) $v)->toArray();
        $avgDaily = count($dailyRevenues) > 0 ? array_sum($dailyRevenues) / count($dailyRevenues) : 0;
        $projected7d = round($avgDaily * 7, 2);

        // Generate text insights
        $topInsight = $this->generateTopInsight($revenueChange, $thisMonth, $tenant);
        $secondaryInsight = $this->generateSecondaryInsight($tenant, $now);

        return [
            'revenue_change' => $revenueChange,
            'order_change' => $orderChange,
            'avg_order_change' => $avgOrderChange,
            'top_insight' => $topInsight,
            'secondary_insight' => $secondaryInsight,
            'projected_revenue_7d' => $projected7d,
        ];
    }

    public function getForecast(Tenant $tenant): array
    {
        $now = Carbon::now();
        $dateFrom = $now->copy()->subDays(89);

        $dailyRevenue = $this->baseDbQuery($tenant, $dateFrom, $now)
            ->selectRaw("date_trunc('day', created_at) as period_date")
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->groupByRaw("date_trunc('day', created_at)")
            ->orderBy('period_date')
            ->get();

        $labels = $dailyRevenue->pluck('period_date')->map(fn ($d) => Carbon::parse($d)->format('M d'))->toArray();
        $revenue = $dailyRevenue->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->toArray();

        $count = count($revenue);
        $ma7 = $this->movingAverage($revenue, 7);
        $ma30 = $this->movingAverage($revenue, 30);

        $midpoint = intdiv($count, 2);
        $firstHalf = $midpoint > 0 ? array_sum(array_slice($revenue, 0, $midpoint)) : 0;
        $secondHalf = $midpoint > 0 ? array_sum(array_slice($revenue, $midpoint)) : 0;
        $growthRate = $firstHalf > 0 ? round((($secondHalf - $firstHalf) / $firstHalf) * 100, 2) : 0;

        $projectedLabels = [];
        $projectedRevenue = [];
        if ($count >= 2) {
            $slope = $this->linearSlope($revenue);
            $lastVal = end($revenue) ?: 0;
            $lastDate = $count > 0 ? Carbon::parse($dailyRevenue->last()->period_date) : $now;

            for ($i = 1; $i <= 30; $i++) {
                $projectedLabels[] = $lastDate->copy()->addDays($i)->format('M d');
                $projectedRevenue[] = round(max(0, $lastVal + $slope * $i), 2);
            }
        }

        $proj7 = round(array_sum(array_slice($projectedRevenue, 0, 7)), 2);
        $proj14 = round(array_sum(array_slice($projectedRevenue, 0, 14)), 2);
        $proj30 = round(array_sum($projectedRevenue), 2);

        $dowPattern = [];
        $dowSums = array_fill(0, 7, 0);
        $dowCounts = array_fill(0, 7, 0);

        foreach ($dailyRevenue as $row) {
            $dow = Carbon::parse($row->period_date)->dayOfWeek;
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

    public function getPeakHours(Tenant $tenant): array
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $totalDays = 30;

        $results = $this->baseDbQuery($tenant, $thirtyDaysAgo, $now)
            ->selectRaw('EXTRACT(HOUR FROM created_at)::int as hour')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->groupByRaw('EXTRACT(HOUR FROM created_at)::int')
            ->orderBy('hour')
            ->get();

        return $results->map(function ($row) use ($totalDays) {
            $hour = (int) $row->hour;
            $period = $hour >= 12 ? 'PM' : 'AM';
            $displayHour = $hour % 12 ?: 12;

            return [
                'hour' => $hour,
                'label' => "{$displayHour}:00 {$period}",
                'avg_orders' => round((int) $row->total_orders / $totalDays, 1),
                'avg_revenue' => round((float) $row->total_revenue / $totalDays, 2),
            ];
        })->toArray();
    }

    public function getProductTrends(Tenant $tenant): array
    {
        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $currentOrderIds = $this->baseQuery($tenant, $thisMonthStart, $now)->pluck('id');
        $previousOrderIds = $this->baseQuery($tenant, $lastMonthStart, $lastMonthEnd)->pluck('id');

        $currentProducts = OrderItem::whereIn('order_id', $currentOrderIds)
            ->select('product_name')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as total_revenue')
            ->groupBy('product_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->keyBy('product_name');

        $productNames = $currentProducts->keys()->toArray();

        $previousProducts = OrderItem::whereIn('order_id', $previousOrderIds)
            ->whereIn('product_name', $productNames)
            ->select('product_name')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as total_revenue')
            ->groupBy('product_name')
            ->get()
            ->keyBy('product_name');

        return $currentProducts->map(function ($item) use ($previousProducts) {
            $currentRev = round((float) $item->total_revenue, 2);
            $prevRow = $previousProducts->get($item->product_name);
            $prevRev = $prevRow ? round((float) $prevRow->total_revenue, 2) : 0;
            $change = $prevRev > 0 ? round((($currentRev - $prevRev) / $prevRev) * 100, 1) : ($currentRev > 0 ? 100 : 0);

            return [
                'name' => $item->product_name,
                'current_revenue' => $currentRev,
                'previous_revenue' => $prevRev,
                'change_percent' => $change,
            ];
        })->values()->toArray();
    }

    public function generateInsights(Tenant $tenant): array
    {
        $insights = [];
        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $thisMonth = $this->periodStats($tenant, $thisMonthStart, $now);
        $lastMonth = $this->periodStats($tenant, $lastMonthStart, $lastMonthEnd);

        // Revenue trend insight
        if ($lastMonth['revenue'] > 0) {
            $change = round((($thisMonth['revenue'] - $lastMonth['revenue']) / $lastMonth['revenue']) * 100, 1);
            if ($change > 5) {
                $insights[] = [
                    'type' => 'positive',
                    'title' => 'Revenue Growing',
                    'description' => "Revenue is up {$change}% compared to last month. Keep up the momentum!",
                    'metric' => "+{$change}%",
                ];
            } elseif ($change < -5) {
                $insights[] = [
                    'type' => 'negative',
                    'title' => 'Revenue Declining',
                    'description' => 'Revenue is down ' . abs($change) . '% compared to last month. Consider running promotions.',
                    'metric' => "{$change}%",
                ];
            } else {
                $insights[] = [
                    'type' => 'neutral',
                    'title' => 'Revenue Stable',
                    'description' => "Revenue is relatively stable ({$change}%) compared to last month.",
                    'metric' => "{$change}%",
                ];
            }
        } elseif ($thisMonth['revenue'] > 0) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'First Sales!',
                'description' => 'Congratulations on your first sales this month!',
            ];
        }

        // Best day of week
        $dowData = $this->baseDbQuery($tenant, $now->copy()->subDays(30), $now)
            ->selectRaw("EXTRACT(DOW FROM created_at)::int as dow")
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->groupByRaw("EXTRACT(DOW FROM created_at)::int")
            ->orderByDesc('revenue')
            ->first();

        if ($dowData && (float) $dowData->revenue > 0) {
            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $bestDay = $dayNames[(int) $dowData->dow] ?? 'Unknown';
            $insights[] = [
                'type' => 'neutral',
                'title' => 'Best Sales Day',
                'description' => "{$bestDay} is your strongest sales day. Consider scheduling promotions around it.",
                'metric' => $bestDay,
            ];
        }

        // Peak hour insight
        $peakHour = $this->baseDbQuery($tenant, $now->copy()->subDays(30), $now)
            ->selectRaw('EXTRACT(HOUR FROM created_at)::int as hour')
            ->selectRaw('COUNT(*) as cnt')
            ->groupByRaw('EXTRACT(HOUR FROM created_at)::int')
            ->orderByDesc('cnt')
            ->first();

        if ($peakHour && (int) $peakHour->cnt > 0) {
            $h = (int) $peakHour->hour;
            $period = $h >= 12 ? 'PM' : 'AM';
            $displayHour = $h % 12 ?: 12;
            $insights[] = [
                'type' => 'neutral',
                'title' => 'Peak Business Hour',
                'description' => "Most orders come in around {$displayHour}:00 {$period}. Ensure adequate staffing.",
                'metric' => "{$displayHour} {$period}",
            ];
        }

        // Product trends
        $productTrends = $this->getProductTrends($tenant);
        $growing = collect($productTrends)->filter(fn ($p) => $p['change_percent'] > 20)->first();
        $declining = collect($productTrends)->filter(fn ($p) => $p['change_percent'] < -20)->first();

        if ($growing) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'Trending Product',
                'description' => "{$growing['name']} is up {$growing['change_percent']}% this month. Consider featuring it prominently.",
                'metric' => "+{$growing['change_percent']}%",
            ];
        }

        if ($declining) {
            $insights[] = [
                'type' => 'negative',
                'title' => 'Declining Product',
                'description' => "{$declining['name']} is down " . abs($declining['change_percent']) . "% this month. Review pricing or availability.",
                'metric' => "{$declining['change_percent']}%",
            ];
        }

        // Order volume insight
        if ($lastMonth['orders'] > 0) {
            $orderChange = round((($thisMonth['orders'] - $lastMonth['orders']) / $lastMonth['orders']) * 100, 1);
            if ($orderChange > 10) {
                $insights[] = [
                    'type' => 'positive',
                    'title' => 'Order Volume Up',
                    'description' => "You're processing {$orderChange}% more orders than last month. Great customer engagement!",
                    'metric' => "+{$orderChange}%",
                ];
            } elseif ($orderChange < -10) {
                $insights[] = [
                    'type' => 'negative',
                    'title' => 'Order Volume Down',
                    'description' => 'Order volume decreased by ' . abs($orderChange) . '%. Consider marketing campaigns.',
                    'metric' => "{$orderChange}%",
                ];
            }
        }

        // Empty state
        if (empty($insights)) {
            $insights[] = [
                'type' => 'neutral',
                'title' => 'Not Enough Data',
                'description' => 'Start processing orders to see AI-powered insights about your business performance.',
            ];
        }

        return $insights;
    }

    private function generateTopInsight(float $revenueChange, array $thisMonth, Tenant $tenant): string
    {
        if ($thisMonth['revenue'] == 0 && $thisMonth['orders'] == 0) {
            return 'Start processing orders to unlock AI insights about your business.';
        }

        if ($revenueChange > 10) {
            return "Revenue is up {$revenueChange}% vs last month — your business is growing!";
        } elseif ($revenueChange < -10) {
            return 'Revenue is down ' . abs($revenueChange) . '% vs last month — consider running promotions.';
        }

        return "Revenue is stable at {$revenueChange}% change vs last month.";
    }

    private function generateSecondaryInsight(Tenant $tenant, Carbon $now): string
    {
        $dowData = $this->baseDbQuery($tenant, $now->copy()->subDays(30), $now)
            ->selectRaw("EXTRACT(DOW FROM created_at)::int as dow")
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->groupByRaw("EXTRACT(DOW FROM created_at)::int")
            ->orderByDesc('revenue')
            ->first();

        if ($dowData && (float) $dowData->revenue > 0) {
            $dayNames = ['Sundays', 'Mondays', 'Tuesdays', 'Wednesdays', 'Thursdays', 'Fridays', 'Saturdays'];
            return "{$dayNames[(int) $dowData->dow]} are your best-performing sales days.";
        }

        return 'Process more orders to discover your peak sales patterns.';
    }

    private function periodStats(Tenant $tenant, $from, $to): array
    {
        $stats = $this->baseDbQuery($tenant, $from, $to)
            ->selectRaw('COALESCE(SUM(total), 0) as revenue, COUNT(*) as orders')
            ->first();

        return [
            'revenue' => round((float) $stats->revenue, 2),
            'orders' => (int) $stats->orders,
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
        if ($n < 2) {
            return 0;
        }

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $data[$i];
            $sumXY += $i * $data[$i];
            $sumX2 += $i * $i;
        }

        $denom = ($n * $sumX2 - $sumX * $sumX);

        return $denom != 0 ? ($n * $sumXY - $sumX * $sumY) / $denom : 0;
    }

    private function baseQuery(Tenant $tenant, $dateFrom, $dateTo): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Tenant\Order::where('tenant_id', $tenant->id)
            ->where('status', OrderStatus::Completed->value)
            ->whereBetween('created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
    }

    private function baseDbQuery(Tenant $tenant, $dateFrom, $dateTo): \Illuminate\Database\Query\Builder
    {
        return DB::table('orders')
            ->where('tenant_id', $tenant->id)
            ->where('status', OrderStatus::Completed->value)
            ->whereBetween('created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
    }
}
