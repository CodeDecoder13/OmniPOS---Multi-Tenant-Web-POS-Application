<?php

namespace App\Services\Tenant;

use App\Enums\AdjustmentType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Tenant;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\InventoryAdjustment;
use App\Models\Tenant\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function list(Tenant $tenant, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseOrder::forTenant($tenant)
            ->with(['supplier:id,name', 'branch:id,name', 'creator:id,name']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($search = $request->input('search')) {
            $query->where('po_number', 'like', "%{$search}%");
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function findForTenant(Tenant $tenant, int $poId): PurchaseOrder
    {
        return PurchaseOrder::forTenant($tenant)
            ->with(['supplier:id,name', 'branch:id,name', 'items.product:id,name,sku', 'creator:id,name'])
            ->findOrFail($poId);
    }

    public function create(Tenant $tenant, array $data, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($tenant, $data, $userId) {
            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $totalAmount += $item['quantity_ordered'] * $item['unit_cost'];
            }

            $po = PurchaseOrder::create([
                'tenant_id' => $tenant->id,
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'po_number' => $this->generatePoNumber($tenant),
                'status' => PurchaseOrderStatus::Draft,
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total_amount' => $totalAmount,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $item['quantity_ordered'] * $item['unit_cost'],
                ]);
            }

            return $po->load(['supplier:id,name', 'branch:id,name', 'items.product:id,name,sku']);
        });
    }

    public function update(PurchaseOrder $po, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data) {
            $po->update([
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $po->items()->delete();

            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $subtotal = $item['quantity_ordered'] * $item['unit_cost'];
                $totalAmount += $subtotal;

                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $subtotal,
                ]);
            }

            $po->update(['total_amount' => $totalAmount]);

            return $po->fresh(['supplier:id,name', 'branch:id,name', 'items.product:id,name,sku']);
        });
    }

    public function send(PurchaseOrder $po): PurchaseOrder
    {
        $po->update(['status' => PurchaseOrderStatus::Sent]);
        return $po->fresh();
    }

    public function receive(PurchaseOrder $po, array $receivedQuantities, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $receivedQuantities, $userId) {
            $allFullyReceived = true;

            foreach ($po->items as $item) {
                $received = $receivedQuantities[$item->id] ?? 0;
                $newTotal = $item->quantity_received + $received;
                $item->update(['quantity_received' => $newTotal]);

                if ($newTotal < $item->quantity_ordered) {
                    $allFullyReceived = false;
                }

                if ($received > 0) {
                    $inventory = Inventory::firstOrCreate(
                        ['product_id' => $item->product_id, 'branch_id' => $po->branch_id],
                        ['tenant_id' => $po->tenant_id, 'quantity_on_hand' => 0, 'low_stock_threshold' => 0]
                    );

                    $qtyBefore = $inventory->quantity_on_hand;
                    $qtyAfter = $qtyBefore + $received;
                    $inventory->update(['quantity_on_hand' => $qtyAfter]);

                    InventoryAdjustment::create([
                        'tenant_id' => $po->tenant_id,
                        'inventory_id' => $inventory->id,
                        'type' => AdjustmentType::Purchase,
                        'quantity_before' => $qtyBefore,
                        'quantity_after' => $qtyAfter,
                        'quantity_change' => $received,
                        'reason' => "PO {$po->po_number}",
                        'reference_type' => 'purchase_order',
                        'reference_id' => $po->id,
                        'created_by' => $userId,
                    ]);
                }
            }

            $status = $allFullyReceived ? PurchaseOrderStatus::Received : PurchaseOrderStatus::Partial;

            $po->update([
                'status' => $status,
                'received_by' => $userId,
                'received_at' => now(),
            ]);

            return $po->fresh(['supplier:id,name', 'branch:id,name', 'items.product:id,name,sku']);
        });
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        $po->update(['status' => PurchaseOrderStatus::Cancelled]);
        return $po->fresh();
    }

    private function generatePoNumber(Tenant $tenant): string
    {
        $today = now()->format('Ymd');
        $prefix = "PO-{$today}-";

        $last = PurchaseOrder::forTenant($tenant)
            ->where('po_number', 'like', "{$prefix}%")
            ->orderByDesc('po_number')
            ->first();

        $next = $last ? ((int) str_replace($prefix, '', $last->po_number)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
