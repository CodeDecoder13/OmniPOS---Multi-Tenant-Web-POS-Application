<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Services\Tenant\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function __construct(
        private readonly ShiftService $shiftService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/shifts/Index', [
            'shifts' => $this->shiftService->list($tenant, $request),
            'filters' => $request->only(['status', 'branch_id', 'date_from', 'date_to']),
            'branches' => Branch::forTenant($tenant)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, string $tenantSlug, int $shift): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $shift = $this->shiftService->findForTenant($tenant, $shift);
        $summary = $this->shiftService->getShiftSummary($shift);

        $orders = $shift->orders()
            ->with(['payments:id,order_id,method,amount,status'])
            ->latest()
            ->get(['id', 'order_number', 'total', 'status', 'created_at', 'shift_id']);

        return Inertia::render('tenant/shifts/Show', [
            'shift' => $shift,
            'summary' => $summary,
            'orders' => $orders,
        ]);
    }

    /**
     * A shift may only be acted on for yourself, or for a cashier who proved their PIN in this
     * session (same rule as checkout). Anything else would let one user run another's till.
     */
    private function assertMayActFor(Request $request, int $userId): void
    {
        if ($userId === (int) $request->user()->id) {
            return;
        }

        $tenant = $request->attributes->get('current_tenant');
        $verifiedAt = $request->session()->get('pos_verified_operator_at');

        $verified = (int) $request->session()->get('pos_verified_operator_id') === $userId
            && $verifiedAt
            && (now()->timestamp - $verifiedAt) <= 8 * 60 * 60;

        abort_unless($verified || (int) $request->user()->id === (int) $tenant->owner_id, 403, 'You cannot act on another user\'s shift.');
    }

    public function open(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');

        $validated = $request->validate([
            'user_id' => 'required|integer',
            'starting_cash' => 'required|numeric|min:0|max:9999999.99',
        ]);

        $this->assertMayActFor($request, (int) $validated['user_id']);

        $shift = $this->shiftService->openShift($tenant, $validated['user_id'], [
            'starting_cash' => $validated['starting_cash'],
            'branch_id' => $tenantUser->branch_id ?? null,
        ]);

        return response()->json([
            'success' => true,
            'shift' => $shift,
        ]);
    }

    public function close(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $validated = $request->validate([
            'shift_id' => 'required|integer',
            'ending_cash' => 'required|numeric|min:0|max:9999999.99',
            'notes' => 'nullable|string|max:500',
        ]);

        $shift = $this->shiftService->findForTenant($tenant, $validated['shift_id']);
        $this->assertMayActFor($request, (int) $shift->user_id);
        $shift = $this->shiftService->closeShift($shift, $validated);
        $summary = $this->shiftService->getShiftSummary($shift);

        return response()->json([
            'success' => true,
            'shift' => $shift,
            'summary' => $summary,
        ]);
    }

    public function status(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $userId = $request->query('user_id');

        if (! $userId) {
            return response()->json(['shift' => null, 'summary' => null]);
        }

        // Shift cash is private to that cashier (or the owner / a PIN-verified operator).
        $this->assertMayActFor($request, (int) $userId);

        $shift = $this->shiftService->getOpenShift($tenant, (int) $userId);

        if (! $shift) {
            return response()->json(['shift' => null, 'summary' => null]);
        }

        $summary = $this->shiftService->getShiftSummary($shift);

        return response()->json([
            'shift' => $shift,
            'summary' => $summary,
        ]);
    }
}
