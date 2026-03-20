<?php

namespace App\Services\Tenant;

use App\Enums\AdjustmentType;
use App\Enums\TransferStatus;
use App\Models\Tenant;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\InventoryAdjustment;
use App\Models\Tenant\StockTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function list(Tenant $tenant, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = StockTransfer::forTenant($tenant)
            ->with(['sourceBranch:id,name', 'destinationBranch:id,name', 'creator:id,name']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($branchId = $request->input('branch_id')) {
            $query->where(function ($q) use ($branchId) {
                $q->where('source_branch_id', $branchId)
                    ->orWhere('destination_branch_id', $branchId);
            });
        }

        if ($search = $request->input('search')) {
            $query->where('transfer_number', 'like', "%{$search}%");
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function findForTenant(Tenant $tenant, int $transferId): StockTransfer
    {
        return StockTransfer::forTenant($tenant)
            ->with(['sourceBranch:id,name', 'destinationBranch:id,name', 'items.product:id,name,sku', 'creator:id,name'])
            ->findOrFail($transferId);
    }

    public function create(Tenant $tenant, array $data, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($tenant, $data, $userId) {
            $transfer = StockTransfer::create([
                'tenant_id' => $tenant->id,
                'transfer_number' => $this->generateTransferNumber($tenant),
                'source_branch_id' => $data['source_branch_id'],
                'destination_branch_id' => $data['destination_branch_id'],
                'status' => TransferStatus::Pending,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_requested' => $item['quantity_requested'],
                ]);
            }

            return $transfer->load(['sourceBranch:id,name', 'destinationBranch:id,name', 'items.product:id,name,sku']);
        });
    }

    public function ship(StockTransfer $transfer, array $sentQuantities, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $sentQuantities, $userId) {
            foreach ($transfer->items as $item) {
                $sent = $sentQuantities[$item->id] ?? $item->quantity_requested;
                $item->update(['quantity_sent' => $sent]);

                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $item->product_id, 'branch_id' => $transfer->source_branch_id],
                    ['tenant_id' => $transfer->tenant_id, 'quantity_on_hand' => 0, 'low_stock_threshold' => 0]
                );

                $qtyBefore = $inventory->quantity_on_hand;
                $qtyAfter = $qtyBefore - $sent;
                $inventory->update(['quantity_on_hand' => $qtyAfter]);

                InventoryAdjustment::create([
                    'tenant_id' => $transfer->tenant_id,
                    'inventory_id' => $inventory->id,
                    'type' => AdjustmentType::TransferOut,
                    'quantity_before' => $qtyBefore,
                    'quantity_after' => $qtyAfter,
                    'quantity_change' => -$sent,
                    'reason' => "Transfer {$transfer->transfer_number}",
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'created_by' => $userId,
                ]);
            }

            $transfer->update([
                'status' => TransferStatus::InTransit,
                'approved_by' => $userId,
            ]);

            return $transfer->fresh(['sourceBranch:id,name', 'destinationBranch:id,name', 'items.product:id,name,sku']);
        });
    }

    public function receive(StockTransfer $transfer, array $receivedQuantities, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $receivedQuantities, $userId) {
            foreach ($transfer->items as $item) {
                $received = $receivedQuantities[$item->id] ?? $item->quantity_sent;
                $item->update(['quantity_received' => $received]);

                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $item->product_id, 'branch_id' => $transfer->destination_branch_id],
                    ['tenant_id' => $transfer->tenant_id, 'quantity_on_hand' => 0, 'low_stock_threshold' => 0]
                );

                $qtyBefore = $inventory->quantity_on_hand;
                $qtyAfter = $qtyBefore + $received;
                $inventory->update(['quantity_on_hand' => $qtyAfter]);

                InventoryAdjustment::create([
                    'tenant_id' => $transfer->tenant_id,
                    'inventory_id' => $inventory->id,
                    'type' => AdjustmentType::TransferIn,
                    'quantity_before' => $qtyBefore,
                    'quantity_after' => $qtyAfter,
                    'quantity_change' => $received,
                    'reason' => "Transfer {$transfer->transfer_number}",
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'created_by' => $userId,
                ]);
            }

            $transfer->update([
                'status' => TransferStatus::Completed,
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);

            return $transfer->fresh(['sourceBranch:id,name', 'destinationBranch:id,name', 'items.product:id,name,sku']);
        });
    }

    public function cancel(StockTransfer $transfer, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            if ($transfer->status === TransferStatus::InTransit) {
                foreach ($transfer->items as $item) {
                    if (!$item->quantity_sent) continue;

                    $inventory = Inventory::where('product_id', $item->product_id)
                        ->where('branch_id', $transfer->source_branch_id)
                        ->first();

                    if ($inventory) {
                        $qtyBefore = $inventory->quantity_on_hand;
                        $qtyAfter = $qtyBefore + $item->quantity_sent;
                        $inventory->update(['quantity_on_hand' => $qtyAfter]);

                        InventoryAdjustment::create([
                            'tenant_id' => $transfer->tenant_id,
                            'inventory_id' => $inventory->id,
                            'type' => AdjustmentType::TransferIn,
                            'quantity_before' => $qtyBefore,
                            'quantity_after' => $qtyAfter,
                            'quantity_change' => $item->quantity_sent,
                            'reason' => "Transfer {$transfer->transfer_number} cancelled - reversal",
                            'reference_type' => 'stock_transfer',
                            'reference_id' => $transfer->id,
                            'created_by' => $userId,
                        ]);
                    }
                }
            }

            $transfer->update(['status' => TransferStatus::Cancelled]);

            return $transfer->fresh(['sourceBranch:id,name', 'destinationBranch:id,name', 'items.product:id,name,sku']);
        });
    }

    private function generateTransferNumber(Tenant $tenant): string
    {
        $today = now()->format('Ymd');
        $prefix = "TRF-{$today}-";

        $last = StockTransfer::forTenant($tenant)
            ->where('transfer_number', 'like', "{$prefix}%")
            ->orderByDesc('transfer_number')
            ->first();

        $next = $last ? ((int) str_replace($prefix, '', $last->transfer_number)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
