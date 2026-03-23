<?php

namespace App\Services\Tenant;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Models\Tenant\Table;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}
    public function list(Tenant $tenant, Request $request, ?int $branchId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::forTenant($tenant)
            ->with(['customer:id,name', 'creator:id,name'])
            ->withCount('items');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function findForTenant(Tenant $tenant, int $orderId, ?int $branchId = null): Order
    {
        $query = Order::forTenant($tenant)
            ->with(['items.product:id,name', 'payments', 'customer', 'creator:id,name', 'branch:id,name', 'voidedByUser:id,name']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->findOrFail($orderId);
    }

    public function voidOrder(Order $order, ?int $userId = null, ?string $voidReason = null): Order
    {
        if (! $order->canBeVoided()) {
            abort(422, 'This order cannot be voided.');
        }

        return DB::transaction(function () use ($order, $userId, $voidReason) {
            $order->update([
                'status' => OrderStatus::Voided,
                'voided_by' => $userId,
                'void_reason' => $voidReason,
                'voided_at' => now(),
            ]);

            $this->inventoryService->incrementForVoid($order, $userId ?? $order->created_by);

            // Reset table to Available if no other active orders use it
            if ($order->table_id) {
                $hasActiveOrders = Order::where('table_id', $order->table_id)
                    ->where('id', '!=', $order->id)
                    ->whereIn('status', [OrderStatus::Completed, OrderStatus::Pending])
                    ->exists();

                if (! $hasActiveOrders) {
                    Table::where('id', $order->table_id)->update(['status' => TableStatus::Available]);
                }
            }

            // Decrement promotion used_count if promotion was applied
            if ($order->promotion_id && $order->promotion) {
                $order->promotion->decrement('used_count');
            }

            return $order->fresh();
        });
    }
}
