<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\AdjustmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InventoryAdjustmentRequest;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Inventory;
use App\Services\Tenant\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'stats' => $this->inventoryService->stats($tenant, $request),
            'branches' => Branch::forTenant($tenant)->where('is_active', true)->select(['id', 'name'])->orderBy('name')->get(),
            'filters' => $request->only(['search', 'branch_id', 'low_stock']),
        ]);
    }

    public function adjust(InventoryAdjustmentRequest $request, string $tenantSlug, int $inventory)
    {
        // Resolve inside this tenant: a bare route-bound model would accept another tenant's id.
        $inventory = $this->inventoryService->findForTenant($request->attributes->get('current_tenant'), $inventory);

        $updateFields = [];
        foreach (['low_stock_threshold', 'reorder_point', 'reorder_quantity'] as $field) {
            if ($request->filled($field)) {
                $updateFields[$field] = $request->validated($field);
            }
        }

        // One transaction: the stock change and the settings either both save or neither does.
        DB::transaction(function () use ($request, $inventory, $updateFields) {
            // Thresholds and reorder settings can be saved without changing stock.
            if ((int) $request->validated('quantity_change') !== 0) {
                $this->inventoryService->adjust(
                    $inventory,
                    AdjustmentType::from($request->validated('type')),
                    $request->validated('quantity_change'),
                    $request->validated('reason'),
                    $request->user()->id,
                );
            }

            if ($updateFields !== []) {
                $inventory->update($updateFields);
            }
        });

        return redirect()->back()->with('success', 'Inventory updated successfully.');
    }

    public function history(Request $request, string $tenantSlug, int $inventory)
    {
        $inventory = $this->inventoryService->findForTenant($request->attributes->get('current_tenant'), $inventory);

        return response()->json(
            $this->inventoryService->getHistory($inventory)
        );
    }
}
