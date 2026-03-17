<?php

namespace App\Services\Tenant;

use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}
    public function list(Tenant $tenant, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::forTenant($tenant)
            ->with(['customer:id,name', 'creator:id,name'])
            ->withCount('items');

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

    public function findForTenant(Tenant $tenant, int $orderId): Order
    {
        return Order::forTenant($tenant)
            ->with(['items.product:id,name', 'payments', 'customer', 'creator:id,name', 'branch:id,name'])
            ->findOrFail($orderId);
    }

    public function voidOrder(Order $order, ?int $userId = null): Order
    {
        if (! $order->canBeVoided()) {
            abort(422, 'This order cannot be voided.');
        }

        return DB::transaction(function () use ($order, $userId) {
            $order->update(['status' => OrderStatus::Voided]);

            $this->inventoryService->incrementForVoid($order, $userId ?? $order->created_by);

            return $order->fresh();
        });
    }
}
