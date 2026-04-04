<?php

namespace App\Services\Tenant;

use App\Enums\AdjustmentType;
use App\Models\Tenant;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\InventoryAdjustment;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function list(Tenant $tenant, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Inventory::forTenant($tenant)
            ->with(['product:id,name,sku,image_path', 'branch:id,name'])
            ->whereHas('product', fn ($q) => $q->where('is_food', false));

        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        return $query->orderBy(
            \App\Models\Tenant\Product::select('name')
                ->whereColumn('products.id', 'inventory.product_id')
                ->limit(1),
            'asc'
        )->paginate($perPage)->withQueryString();
    }

    public function findForTenant(Tenant $tenant, int $inventoryId): Inventory
    {
        return Inventory::forTenant($tenant)
            ->with(['product:id,name,sku,image_path', 'branch:id,name'])
            ->findOrFail($inventoryId);
    }

    public function adjust(Inventory $inventory, AdjustmentType $type, int $quantityChange, ?string $reason, ?int $userId): InventoryAdjustment
    {
        return DB::transaction(function () use ($inventory, $type, $quantityChange, $reason, $userId) {
            $inventory = Inventory::where('id', $inventory->id)->lockForUpdate()->first();

            $quantityBefore = $inventory->quantity_on_hand;
            $quantityAfter = $quantityBefore + $quantityChange;

            if ($quantityAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Adjustment would result in negative stock ({$quantityAfter}).",
                ]);
            }

            $inventory->update(['quantity_on_hand' => $quantityAfter]);

            return InventoryAdjustment::create([
                'tenant_id' => $inventory->tenant_id,
                'inventory_id' => $inventory->id,
                'type' => $type,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'quantity_change' => $quantityChange,
                'reason' => $reason,
                'created_by' => $userId,
            ]);
        });
    }

    public function decrementForSale(Tenant $tenant, ?int $branchId, array $items, int $orderId, int $userId): void
    {
        if (! $branchId) {
            return;
        }

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];

            $product = Product::find($productId);
            if ($product && $product->is_food) {
                continue;
            }

            $inventory = Inventory::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                $inventory = Inventory::create([
                    'product_id' => $productId,
                    'branch_id' => $branchId,
                    'tenant_id' => $tenant->id,
                    'quantity_on_hand' => 0,
                    'low_stock_threshold' => 0,
                ]);
                $inventory = Inventory::where('id', $inventory->id)->lockForUpdate()->first();
            }

            if ($inventory->quantity_on_hand < $quantity) {
                $product = Product::find($productId);
                throw ValidationException::withMessages([
                    'items' => "Insufficient stock for {$product->name}. Available: {$inventory->quantity_on_hand}, requested: {$quantity}.",
                ]);
            }

            $quantityBefore = $inventory->quantity_on_hand;
            $quantityAfter = $quantityBefore - $quantity;

            $inventory->update(['quantity_on_hand' => $quantityAfter]);

            InventoryAdjustment::create([
                'tenant_id' => $tenant->id,
                'inventory_id' => $inventory->id,
                'type' => AdjustmentType::Sale,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'quantity_change' => -$quantity,
                'reason' => null,
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'created_by' => $userId,
            ]);
        }
    }

    public function incrementForVoid(Order $order, int $userId): void
    {
        if (! $order->branch_id) {
            return;
        }

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $product = Product::find($item->product_id);
            if ($product && $product->is_food) {
                continue;
            }

            $inventory = Inventory::where('product_id', $item->product_id)
                ->where('branch_id', $order->branch_id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                continue;
            }

            $quantityBefore = $inventory->quantity_on_hand;
            $quantityAfter = $quantityBefore + $item->quantity;

            $inventory->update(['quantity_on_hand' => $quantityAfter]);

            InventoryAdjustment::create([
                'tenant_id' => $order->tenant_id,
                'inventory_id' => $inventory->id,
                'type' => AdjustmentType::Return,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'quantity_change' => $item->quantity,
                'reason' => "Order {$order->order_number} voided",
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'created_by' => $userId,
            ]);
        }
    }

    public function getHistory(Inventory $inventory, int $perPage = 15): LengthAwarePaginator
    {
        return $inventory->adjustments()
            ->with('creator:id,name')
            ->latest()
            ->paginate($perPage);
    }
}
