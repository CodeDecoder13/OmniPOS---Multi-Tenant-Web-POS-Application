<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CheckoutRequest;
use App\Models\Tenant\Category;
use App\Models\Tenant\Branch;
use App\Models\TenantUser;
use App\Services\Tenant\CustomerService;
use App\Services\Tenant\PosService;
use App\Services\Tenant\ShiftService;
use App\Services\Tenant\TableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function __construct(
        private readonly PosService $posService,
        private readonly CustomerService $customerService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $branchId = $tenantUser->branch_id ?? null;

        $branch = $branchId ? Branch::find($branchId) : null;

        return Inertia::render('tenant/pos/Index', [
            'categories' => Category::forTenant($tenant)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'tables' => app(TableService::class)->getAvailableForBranch($tenant, $branchId),
            'branchSettings' => $branch?->getSettings(),
        ]);
    }

    public function products(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $branchId = $tenantUser->branch_id ?? null;

        return response()->json(
            $this->posService->getProducts($tenant, $request, $branchId)
        );
    }

    public function searchCustomers(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        return response()->json(
            $this->customerService->searchForPos($tenant, $request->input('search'))
        );
    }

    public function checkout(CheckoutRequest $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');

        $operatorId = $request->validated('pos_operator_id');
        if ($operatorId && (int) $operatorId !== (int) $request->user()->id) {
            // Operator must exist in this tenant
            $exists = TenantUser::where('tenant_id', $tenant->id)
                ->where('user_id', $operatorId)->exists();
            if (! $exists) {
                return response()->json(['message' => 'Invalid POS operator.'], 422);
            }

            // Operator must have been verified via PIN (session proof)
            $verifiedId = $request->session()->get('pos_verified_operator_id');
            $verifiedAt = $request->session()->get('pos_verified_operator_at');
            $maxAge = 8 * 60 * 60; // 8 hours

            if ((int) $verifiedId !== (int) $operatorId || ! $verifiedAt || (now()->timestamp - $verifiedAt) > $maxAge) {
                return response()->json(['message' => 'POS operator must verify their PIN first.'], 403);
            }
        } else {
            $operatorId = $request->user()->id;
        }

        $activeShift = app(ShiftService::class)->getOpenShift($tenant, $operatorId);

        if (! $activeShift) {
            return response()->json(['message' => 'No active shift. Please open a shift before processing orders.'], 422);
        }

        $checkoutData = $request->validated();
        $checkoutData['shift_id'] = $activeShift->id;

        $order = $this->posService->checkout(
            $tenant,
            $checkoutData,
            $operatorId,
            $tenantUser->branch_id ?? null,
        );

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }
}
