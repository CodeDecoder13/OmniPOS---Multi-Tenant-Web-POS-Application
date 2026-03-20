<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\KitchenStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Order;
use App\Services\Tenant\KitchenDisplayService;
use App\Services\Tenant\KotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class KitchenDisplayController extends Controller
{
    public function __construct(
        private readonly KitchenDisplayService $kitchenService,
        private readonly KotService $kotService,
    ) {}

    public function index(Request $request, string $tenantSlug): InertiaResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $branchId = $tenantUser->branch_id ?? null;

        $branch = $branchId ? Branch::find($branchId) : null;

        $orders = $branchId
            ? $this->kitchenService->getActiveOrders($tenant, $branchId)
            : collect();

        return Inertia::render('tenant/kitchen/Index', [
            'orders' => $orders,
            'branchName' => $branch?->name ?? 'No Branch',
            'kitchenEnabled' => $branch?->getSetting('kitchen_display') ?? false,
        ]);
    }

    public function poll(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $branchId = $tenantUser->branch_id ?? null;

        if (! $branchId) {
            return response()->json(['orders' => []]);
        }

        $since = $request->query('since');

        $orders = $this->kitchenService->getOrdersForPolling($tenant, $branchId, $since);

        return response()->json([
            'orders' => $orders,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function updateStatus(Request $request, string $tenantSlug, Order $order): JsonResponse
    {
        $request->validate([
            'kitchen_status' => ['required', 'string', 'in:new,preparing,ready,served'],
            'kitchen_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $status = KitchenStatus::from($request->input('kitchen_status'));
        $notes = $request->input('kitchen_notes');

        $updated = $this->kitchenService->updateStatus($order, $status, $notes);

        return response()->json(['order' => $updated]);
    }

    public function kotPdf(Request $request, string $tenantSlug, Order $order): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return $this->kotService->generatePdf($tenant, $order);
    }
}
