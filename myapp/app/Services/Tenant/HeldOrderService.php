<?php

namespace App\Services\Tenant;

use App\Enums\OrderStatus;
use App\Events\OrderHeld;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HeldOrderService
{
    public function hold(Tenant $tenant, array $data, int $userId, ?int $branchId = null): Order
    {
        return DB::transaction(function () use ($tenant, $data, $userId, $branchId) {
            $order = Order::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branchId,
                'customer_id' => $data['customer_id'] ?? null,
                'order_number' => $this->generateHeldNumber($tenant),
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'notes' => $data['notes'] ?? null,
                'order_type' => $data['order_type'] ?? 'dine_in',
                'status' => OrderStatus::Pending,
                'created_by' => $userId,
                'held_at' => now(),
                'table_id' => $data['table_id'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $orderItem = $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_price' => $item['product_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['product_price'] * $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);

                if (! empty($item['variations'])) {
                    foreach ($item['variations'] as $v) {
                        $orderItem->variations()->create([
                            'variation_group_name' => $v['variation_group_name'],
                            'option_name' => $v['option_name'],
                            'price_modifier' => $v['price_modifier'] ?? 0,
                        ]);
                    }
                }

                if (! empty($item['addons'])) {
                    foreach ($item['addons'] as $a) {
                        $orderItem->itemAddons()->create([
                            'addon_name' => $a['addon_name'],
                            'addon_price' => $a['addon_price'] ?? 0,
                        ]);
                    }
                }
            }

            $order = $order->load(['items.variations', 'items.itemAddons', 'customer:id,name']);

            OrderHeld::dispatch($order, $tenant);

            return $order;
        });
    }

    public function listHeld(Tenant $tenant, ?int $branchId = null): Collection
    {
        $query = Order::forTenant($tenant)
            ->where('status', OrderStatus::Pending)
            ->whereNotNull('held_at')
            ->with(['items.variations', 'items.itemAddons', 'customer:id,name', 'creator:id,name'])
            ->latest('held_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    public function recall(Order $order): array
    {
        $order->load(['items.variations', 'items.itemAddons', 'customer:id,name']);

        return [
            'order_id' => $order->id,
            'customer' => $order->customer,
            'notes' => $order->notes,
            'order_type' => $order->order_type->value,
            'table_id' => $order->table_id,
            'items' => $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_price' => (float) $item->product_price,
                    'quantity' => $item->quantity,
                    'notes' => $item->notes,
                    'variations' => $item->variations->map(fn ($v) => [
                        'variation_group_name' => $v->variation_group_name,
                        'option_name' => $v->option_name,
                        'price_modifier' => (float) $v->price_modifier,
                    ])->toArray(),
                    'addons' => $item->itemAddons->map(fn ($a) => [
                        'addon_name' => $a->addon_name,
                        'addon_price' => (float) $a->addon_price,
                    ])->toArray(),
                ];
            })->toArray(),
        ];
    }

    public function delete(Order $order): void
    {
        $order->items()->delete();
        $order->delete();
    }

    private function generateHeldNumber(Tenant $tenant): string
    {
        $today = now()->format('Ymd');
        $prefix = "HLD-{$today}-";

        $last = Order::forTenant($tenant)
            ->where('order_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderByDesc('order_number')
            ->first();

        $nextNumber = $last ? (int) str_replace($prefix, '', $last->order_number) + 1 : 1;

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
