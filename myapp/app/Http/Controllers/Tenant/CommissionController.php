<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CommissionPayout;
use App\Services\Tenant\ActivityLogService;
use App\Services\Tenant\CommissionService;
use App\Support\BusinessTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommissionController extends Controller
{
    public function __construct(
        private readonly CommissionService $commissions,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $tenant = $request->attributes->get('current_tenant');

        // Default: this month so far, in the shop's own calendar.
        $from = $validated['date_from'] ?? BusinessTime::today()->startOfMonth()->toDateString();
        $to = $validated['date_to'] ?? BusinessTime::today()->toDateString();
        $branchId = ! empty($validated['branch_id'])
            ? Branch::forTenant($tenant)->findOrFail($validated['branch_id'])->id
            : null;

        return Inertia::render('tenant/staff/Commissions', [
            ...$this->commissions->report($tenant, $from, $to, $branchId),
            'filters' => ['date_from' => $from, 'date_to' => $to, 'branch_id' => $branchId],
            'branches' => Branch::forTenant($tenant)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** "We paid them." Amounts are recomputed on the server and saved as a snapshot. */
    public function pay(Request $request, string $tenantSlug): RedirectResponse
    {
        $validated = $request->validate([
            'staff_member_id' => ['required', 'integer'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $tenant = $request->attributes->get('current_tenant');

        $payout = $this->commissions->markPaid(
            $tenant,
            (int) $validated['staff_member_id'],
            $validated['date_from'],
            $validated['date_to'],
            $request->user()->id,
        );

        $this->activityLog->log($tenant, $request->user()->id, 'commission.paid', 'CommissionPayout', $payout->id, [
            'amount' => (float) $payout->amount,
            'from' => $validated['date_from'],
            'to' => $validated['date_to'],
        ]);

        return back()->with('success', 'Payout recorded.');
    }

    /** Undo a payout recorded by mistake. */
    public function undoPayout(Request $request, string $tenantSlug, int $payout): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $record = CommissionPayout::forTenant($tenant)->findOrFail($payout);
        $record->delete();

        $this->activityLog->log($tenant, $request->user()->id, 'commission.payout_undone', 'CommissionPayout', $record->id, [
            'amount' => (float) $record->amount,
        ]);

        return back()->with('success', 'Payout undone.');
    }
}
