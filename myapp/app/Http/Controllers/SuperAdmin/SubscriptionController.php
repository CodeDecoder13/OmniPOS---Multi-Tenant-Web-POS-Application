<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\TenantSubscription;
use App\Services\Central\AdminActivityLogService;
use App\Services\Central\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private AdminActivityLogService $activityLog,
    ) {}

    public function index(Request $request): Response
    {
        $subscriptions = $this->subscriptionService->list($request->only(['search', 'status', 'plan_id']));

        return Inertia::render('admin/subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'filters' => $request->only(['search', 'status', 'plan_id']),
            'plans' => Plan::all(['id', 'name']),
        ]);
    }

    public function show(int $id): Response
    {
        $subscription = $this->subscriptionService->show($id);

        return Inertia::render('admin/subscriptions/Show', [
            'subscription' => $subscription,
            'plans' => Plan::active()->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $subscription = TenantSubscription::findOrFail($id);
        $validated = $request->validate(['plan_id' => ['required', 'exists:plans,id']]);

        $subscription = $this->subscriptionService->changePlan($subscription, $validated['plan_id']);

        $this->activityLog->log(
            $request->user('admin'),
            'changed_subscription_plan',
            $subscription,
            ['tenant' => $subscription->tenant?->name],
            $request->ip(),
        );

        return back()->with('success', 'Subscription plan updated successfully.');
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        $subscription = TenantSubscription::findOrFail($id);
        $this->subscriptionService->cancel($subscription);

        $this->activityLog->log(
            $request->user('admin'),
            'cancelled_subscription',
            $subscription,
            ['tenant' => $subscription->tenant?->name],
            $request->ip(),
        );

        return back()->with('success', 'Subscription cancelled.');
    }

    public function reactivate(Request $request, int $id): RedirectResponse
    {
        $subscription = TenantSubscription::findOrFail($id);
        $this->subscriptionService->reactivate($subscription);

        $this->activityLog->log(
            $request->user('admin'),
            'reactivated_subscription',
            $subscription,
            ['tenant' => $subscription->tenant?->name],
            $request->ip(),
        );

        return back()->with('success', 'Subscription reactivated.');
    }

    public function extendTrial(Request $request, int $id): RedirectResponse
    {
        $subscription = TenantSubscription::findOrFail($id);
        $validated = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:365']]);

        $this->subscriptionService->extendTrial($subscription, $validated['days']);

        $this->activityLog->log(
            $request->user('admin'),
            'extended_trial',
            $subscription,
            ['tenant' => $subscription->tenant?->name, 'days' => $validated['days']],
            $request->ip(),
        );

        return back()->with('success', "Trial extended by {$validated['days']} days.");
    }
}
