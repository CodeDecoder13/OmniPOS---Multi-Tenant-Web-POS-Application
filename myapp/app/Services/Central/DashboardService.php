<?php

namespace App\Services\Central;

use App\Models\AdminActivityLog;
use App\Models\PageVisit;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getStats(): array
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();
        $totalUsers = User::count();

        $mrr = TenantSubscription::where('tenant_subscriptions.status', 'active')
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

            $revenue = TenantSubscription::where('tenant_subscriptions.status', 'active')
                ->where('tenant_subscriptions.created_at', '<=', $date->endOfMonth())
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

    public function getRecentUserLogins(int $limit = 10): array
    {
        return UserLogin::with(['user:id,name,email', 'tenant:id,name'])
            ->latest('logged_in_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getUserActivityStats(): array
    {
        $now = Carbon::now();

        $activeSessions = DB::table('sessions')
            ->where('last_activity', '>=', $now->subMinutes(15)->timestamp)
            ->count();

        return [
            'logins_today' => UserLogin::whereDate('logged_in_at', $now->toDateString())->count(),
            'logins_week' => UserLogin::where('logged_in_at', '>=', $now->copy()->startOfWeek())->count(),
            'logins_month' => UserLogin::where('logged_in_at', '>=', $now->copy()->startOfMonth())->count(),
            'active_sessions' => $activeSessions,
        ];
    }

    public function getLoginTrend(int $days = 30): array
    {
        $labels = [];
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M d');
            $data[] = UserLogin::whereDate('logged_in_at', $date->toDateString())->count();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getPageVisitStats(): array
    {
        $now = Carbon::now();

        return [
            'visits_today' => PageVisit::whereDate('visited_at', $now->toDateString())->count(),
            'visits_week' => PageVisit::where('visited_at', '>=', $now->copy()->startOfWeek())->count(),
            'visits_month' => PageVisit::where('visited_at', '>=', $now->copy()->startOfMonth())->count(),
            'unique_today' => PageVisit::whereDate('visited_at', $now->toDateString())
                ->distinct('ip_address')->count('ip_address'),
        ];
    }

    public function getPageVisitTrend(int $days = 30): array
    {
        $labels = [];
        $total = [];
        $unique = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M d');
            $total[] = PageVisit::whereDate('visited_at', $date->toDateString())->count();
            $unique[] = PageVisit::whereDate('visited_at', $date->toDateString())
                ->distinct('ip_address')->count('ip_address');
        }

        return ['labels' => $labels, 'total' => $total, 'unique' => $unique];
    }

    public function getTopReferrers(int $limit = 5): array
    {
        return PageVisit::whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->select('referrer', DB::raw('COUNT(*) as count'))
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
