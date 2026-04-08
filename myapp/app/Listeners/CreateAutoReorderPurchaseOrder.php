<?php

namespace App\Listeners;

use App\Enums\PurchaseOrderStatus;
use App\Events\LowStockReached;
use App\Models\Tenant\PurchaseOrder;
use App\Services\Tenant\PurchaseOrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class CreateAutoReorderPurchaseOrder implements ShouldQueue
{
    public function __construct(
        private PurchaseOrderService $purchaseOrderService,
    ) {}

    public function handle(LowStockReached $event): void
    {
        $inventory = $event->inventory;
        $tenant = $event->tenant;

        // Only proceed if reorder is configured
        if (! $inventory->reorder_point || ! $inventory->reorder_quantity) {
            return;
        }

        // Only reorder if stock is at or below reorder point
        if ($inventory->quantity_on_hand > $inventory->reorder_point) {
            return;
        }

        // Check no existing open PO for same product+branch
        $existingPo = PurchaseOrder::where('tenant_id', $tenant->id)
            ->where('branch_id', $inventory->branch_id)
            ->whereIn('status', [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Sent])
            ->whereHas('items', function ($q) use ($inventory) {
                $q->where('product_id', $inventory->product_id);
            })
            ->exists();

        if ($existingPo) {
            return;
        }

        // Find preferred supplier for this product
        $supplierLink = DB::table('product_supplier')
            ->where('product_id', $inventory->product_id)
            ->orderByDesc('is_preferred')
            ->first();

        if (! $supplierLink) {
            return;
        }

        // Create draft PO
        $this->purchaseOrderService->create($tenant, [
            'supplier_id' => $supplierLink->supplier_id,
            'branch_id' => $inventory->branch_id,
            'notes' => 'Auto-generated reorder for low stock.',
            'items' => [
                [
                    'product_id' => $inventory->product_id,
                    'quantity_ordered' => $inventory->reorder_quantity,
                    'unit_cost' => (float) ($supplierLink->cost_price ?? 0),
                ],
            ],
        ], $tenant->owner_id);
    }
}
