<?php

namespace App\Services\Central;

use App\Models\AdminActivityLog;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getStats(): array
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();
        $totalUsers = User::count();

        $mrr = TenantSubscription::where('status', 'active')
            ->join('plans', 'tenant_subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');

        $trialTenants = TenantSubscription::where('status', 'trial')->count();

        $lastMonth = Carbon::now()->subMonth();
        $newSignups = Tenant::where('created_at', '>=', $lastMonth)->count();

        $previousMonthTenants = Tenant::where('created_at', '<', $lastMonth)
            ->where('is_active', true)->count();
        $churnRate = $previousMonthTenants > 0
            ? round(Tenant::where('is_active', false)->where('updated_at', '>=', $lastMonth)->count() / $previousMonthTenants * 100, 1)
            : 0;

        $avgRevenue = $activeTenants > 0 ? round($mrr / $activeTenants, 2) : 0;

        return [
            'total_tenants' => $totalTenants,
            'active_tenants' => $activeTenants,
            'total_users' => $totalUsers,
            'mrr' => $mrr,
            'trial_tenants' => $trialTenants,
            'new_signups' => $newSignups,
            'churn_rate' => $churnRate,
            'avg_revenue' => $avgRevenue,
        ];
    }

    public function getRevenueTrend(int $months = 12): array
    {
        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M Y');

            $revenue = TenantSubscription::where('status', 'active')
                ->where('created_at', '<=', $date->endOfMonth())
                ->join('plans', 'tenant_subscriptions.plan_id', '=', 'plans.id')
                ->sum('plans.price');

            $data[] = round((float) $revenue, 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getPlanDistribution(): array
    {
        return Plan::withCount(['subscriptions' => function ($q) {
            $q->where('status', 'active');
        }])->get()->map(fn ($plan) => [
            'name' => $plan->name,
            'count' => $plan->subscriptions_count,
        ])->toArray();
    }

    public function getRecentActivity(int $limit = 10): array
    {
        return AdminActivityLog::with('admin:id,name')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
