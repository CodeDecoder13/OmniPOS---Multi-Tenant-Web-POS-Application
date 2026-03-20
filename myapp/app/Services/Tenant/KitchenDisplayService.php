<?php

namespace App\Services\Tenant;

use App\Enums\KitchenStatus;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use Illuminate\Database\Eloquent\Collection;

class KitchenDisplayService
{
    public function getActiveOrders(Tenant $tenant, int $branchId): Collection
    {
        return Order::forTenant($tenant)
            ->forKitchen($branchId)
            ->with(['items.variations', 'items.itemAddons', 'table:id,name', 'creator:id,name'])
            ->orderByRaw("CASE
                WHEN kitchen_status = ? THEN 1
                WHEN kitchen_status = ? THEN 2
                WHEN kitchen_status = ? THEN 3
                ELSE 4
            END", [
                KitchenStatus::New->value,
                KitchenStatus::Preparing->value,
                KitchenStatus::Ready->value,
            ])
            ->orderBy('kitchen_sent_at')
            ->get();
    }

    public function getOrdersForPolling(Tenant $tenant, int $branchId, ?string $since = null): Collection
    {
        $query = Order::forTenant($tenant)
            ->where('branch_id', $branchId)
            ->whereNotNull('kitchen_status')
            ->with(['items.variations', 'items.itemAddons', 'table:id,name', 'creator:id,name']);

        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        return $query->orderBy('updated_at')->get();
    }

    public function updateStatus(Order $order, KitchenStatus $status, ?string $notes = null): Order
    {
        $data = ['kitchen_status' => $status];

        if ($status === KitchenStatus::Ready) {
            $data['kitchen_completed_at'] = now();
        }

        if ($notes !== null) {
            $data['kitchen_notes'] = $notes;
        }

        $order->update($data);

        return $order->fresh();
    }
}
