<?php

namespace App\Services\Central;

use App\Enums\SubscriptionStatus;
use App\Models\TenantSubscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubscriptionService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TenantSubscription::with(['tenant', 'plan', 'promoCode']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('tenant', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['plan_id'])) {
            $query->where('plan_id', $filters['plan_id']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function show(int $id): TenantSubscription
    {
        return TenantSubscription::with(['tenant.owner', 'plan', 'promoCode'])->findOrFail($id);
    }

    public function changePlan(TenantSubscription $subscription, int $planId): TenantSubscription
    {
        $subscription->update(['plan_id' => $planId]);

        return $subscription->fresh(['tenant', 'plan']);
    }

    public function cancel(TenantSubscription $subscription): TenantSubscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'ends_at' => now(),
        ]);

        return $subscription->fresh();
    }

    public function reactivate(TenantSubscription $subscription): TenantSubscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'ends_at' => null,
        ]);

        return $subscription->fresh();
    }

    public function extendTrial(TenantSubscription $subscription, int $days): TenantSubscription
    {
        $currentEnd = $subscription->trial_ends_at ?? now();

        $subscription->update([
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => $currentEnd->addDays($days),
        ]);

        return $subscription->fresh();
    }
}
