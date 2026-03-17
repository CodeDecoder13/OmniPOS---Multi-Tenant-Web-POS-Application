<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\AdjustmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InventoryAdjustmentRequest;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Inventory;
use App\Services\Tenant\InventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function index(Request $request, string $tenantSlug)
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/inventory/Index', [
            'inventory' => $this->inventoryService->list($tenant, $request),
            'branches' => Branch::forTenant($tenant)->where('is_active', true)->select(['id', 'name'])->orderBy('name')->get(),
            'filters' => $request->only(['search', 'branch_id', 'low_stock']),
        ]);
    }

    public function adjust(InventoryAdjustmentRequest $request, string $tenantSlug, Inventory $inventory)
    {
        $this->inventoryService->adjust(
            $inventory,
            AdjustmentType::from($request->validated('type')),
            $request->validated('quantity_change'),
            $request->validated('reason'),
            $request->user()->id,
        );

        if ($request->has('low_stock_threshold')) {
            $inventory->update(['low_stock_threshold' => $request->validated('low_stock_threshold')]);
        }

        return redirect()->back()->with('success', 'Inventory adjusted successfully.');
    }

    public function history(Request $request, string $tenantSlug, Inventory $inventory)
    {
        return response()->json(
            $this->inventoryService->getHistory($inventory)
        );
    }
}
