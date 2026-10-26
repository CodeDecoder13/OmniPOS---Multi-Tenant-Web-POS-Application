<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Services\Tenant\CommissionService;
use App\Support\BusinessTime;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommissionController extends Controller
{
    public function __construct(private readonly CommissionService $commissions) {}

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
}
