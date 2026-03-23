<?php

namespace App\Services\Central;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function revenueReport(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::parse($dateFrom);
        $to = Carbon::parse($dateTo);

        $monthlyRevenue = [];
        $current = $from->copy()->startOfMonth();

        while ($current->lte($to)) {
            $monthEnd = $current->copy()->endOfMonth();

            $revenue = TenantSubscription::where('tenant_subscriptions.status', 'active')
                ->where('tenant_subscriptions.created_at', '<=', $monthEnd)
                ->join('plans', 'tenant_subscriptions.plan_id', '=', 'plans.id')
                ->sum('plans.price');

            $monthlyRevenue[] = [
                'period' => $current->format('M Y'),
                'revenue' => round((float) $revenue, 2),
            ];

            $current->addMonth();
        }

        $planBreakdown = Plan::withCount(['subscriptions' => function ($q) {
            $q->where('status', 'active');
        }])->get()->map(fn ($plan) => [
            'name' => $plan->name,
            'subscribers' => $plan->subscriptions_count,
            'revenue' => round($plan->subscriptions_count * (float) $plan->price, 2),
        ])->toArray();

        $totalMrr = TenantSubscription::where('tenant_subscriptions.status', 'active')
            ->join('plans', 'tenant_subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');

        return [
            'monthly_revenue' => $monthlyRevenue,
            'plan_breakdown' => $planBreakdown,
            'total_mrr' => round((float) $totalMrr, 2),
        ];
    }

    public function tenantReport(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::parse($dateFrom);
        $to = Carbon::parse($dateTo);

        $signupsTrend = [];
        $current = $from->copy()->startOfMonth();

        while ($current->lte($to)) {
            $monthEnd = $current->copy()->endOfMonth();
            $signupsTrend[] = [
                'period' => $current->format('M Y'),
                'count' => Tenant::whereBetween('created_at', [$current, $monthEnd])->count(),
            ];
            $current->addMonth();
        }

        $businessTypes = Tenant::select('business_type', DB::raw('count(*) as count'))
            ->groupBy('business_type')
            ->get()
            ->map(fn ($item) => [
                'type' => $item->business_type->label(),
                'count' => $item->count,
            ])->toArray();

        $activeTenants = Tenant::where('is_active', true)->count();
        $inactiveTenants = Tenant::where('is_active', false)->count();

        return [
            'signups_trend' => $signupsTrend,
            'business_types' => $businessTypes,
            'active_count' => $activeTenants,
            'inactive_count' => $inactiveTenants,
            'total' => $activeTenants + $inactiveTenants,
        ];
    }

    public function userReport(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::parse($dateFrom);
        $to = Carbon::parse($dateTo);

        $registrationsTrend = [];
        $current = $from->copy()->startOfMonth();

        while ($current->lte($to)) {
            $monthEnd = $current->copy()->endOfMonth();
            $registrationsTrend[] = [
                'period' => $current->format('M Y'),
                'count' => User::whereBetween('created_at', [$current, $monthEnd])->count(),
            ];
            $current->addMonth();
        }

        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $unverifiedUsers = $totalUsers - $verifiedUsers;

        return [
            'registrations_trend' => $registrationsTrend,
            'total_users' => $totalUsers,
            'verified_users' => $verifiedUsers,
            'unverified_users' => $unverifiedUsers,
        ];
    }

    public function subscriptionReport(): array
    {
        $planDistribution = Plan::withCount('subscriptions')
            ->get()
            ->map(fn ($plan) => [
                'name' => $plan->name,
                'count' => $plan->subscriptions_count,
            ])->toArray();

        $statusBreakdown = TenantSubscription::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(fn ($item) => [
                'status' => $item->status->label(),
                'count' => $item->count,
            ])->toArray();

        $trialConversions = TenantSubscription::where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->count();

        $totalTrials = TenantSubscription::whereNotNull('trial_ends_at')->count();

        return [
            'plan_distribution' => $planDistribution,
            'status_breakdown' => $statusBreakdown,
            'trial_conversions' => $trialConversions,
            'total_trials' => $totalTrials,
            'conversion_rate' => $totalTrials > 0 ? round($trialConversions / $totalTrials * 100, 1) : 0,
        ];
    }
}
