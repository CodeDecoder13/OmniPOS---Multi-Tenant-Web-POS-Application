<?php

namespace App\Services\Tenant;

use App\Enums\AdjustmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderRefunded;
use App\Models\Tenant;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\InventoryAdjustment;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\Refund;
use App\Models\Tenant\RefundItem;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function processRefund(Tenant $tenant, Order $order, array $data, int $userId): Refund
    {
        return DB::transaction(function () use ($tenant, $order, $data, $userId) {
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            if (! $order->canBeRefunded()) {
                abort(422, 'This order cannot be refunded.');
            }

            // Quantity already refunded per line, so a second refund can never restock the same units again.
            $refundedQty = RefundItem::whereIn('order_item_id', $order->items->pluck('id'))
                ->groupBy('order_item_id')
                ->selectRaw('order_item_id, SUM(quantity) as qty')
                ->pluck('qty', 'order_item_id');

            $refundNumber = $this->generateRefundNumber($tenant);
            $refundItems = [];
            $totalRefundAmount = 0;

            if ($data['type'] === 'full') {
                // Full refund — refund remaining unrefunded amount
                $totalRefundAmount = (float) $order->total - (float) $order->refunded_amount;

                foreach ($order->items as $item) {
                    $remaining = $item->quantity - (int) ($refundedQty[$item->id] ?? 0);
                    if ($remaining <= 0) {
                        continue;
                    }

                    $refundItems[] = [
                        'order_item_id' => $item->id,
                        'quantity' => $remaining,
                        'amount' => round((float) $item->subtotal * $remaining / max(1, $item->quantity), 2),
                    ];
                }
            } else {
                // Partial refund — refund selected items
                foreach ($data['items'] as $refundItemData) {
                    $orderItem = $order->items->firstWhere('id', $refundItemData['order_item_id']);
                    if (! $orderItem) {
                        continue;
                    }

                    $qty = $refundItemData['quantity'];

                    $remaining = $orderItem->quantity - (int) ($refundedQty[$orderItem->id] ?? 0);
                    if ($qty > $remaining) {
                        abort(422, 'Cannot refund more than the quantity still refundable.');
                    }
                    $unitPrice = (float) $orderItem->product_price;
                    $itemAmount = round($unitPrice * $qty, 2);
                    $totalRefundAmount += $itemAmount;

                    $refundItems[] = [
                        'order_item_id' => $orderItem->id,
                        'quantity' => $qty,
                        'amount' => $itemAmount,
                    ];
                }
            }

            if ($totalRefundAmount <= 0) {
                abort(422, 'Refund amount must be greater than zero.');
            }

            $maxRefundable = (float) $order->total - (float) $order->refunded_amount;
            if ($totalRefundAmount > $maxRefundable) {
                abort(422, 'Refund amount exceeds the refundable balance.');
            }

            // Create refund record
            $refund = Refund::create([
                'tenant_id' => $tenant->id,
                'order_id' => $order->id,
                'refund_number' => $refundNumber,
                'type' => $data['type'],
                'amount' => $totalRefundAmount,
                'reason' => $data['reason'] ?? null,
                'created_by' => $userId,
            ]);

            // Create refund items
            foreach ($refundItems as $ri) {
                $refund->items()->create($ri);
            }

            // Create negative payment record
            $originalPayment = $order->payments()->where('status', PaymentStatus::Completed)->first();
            $order->payments()->create([
                'refund_id' => $refund->id,
                'amount' => -$totalRefundAmount,
                'method' => $originalPayment?->method ?? 'cash',
                'status' => PaymentStatus::Completed,
                'reference_number' => "REFUND:{$refundNumber}",
            ]);

            // Update order refunded amount
            $newRefundedAmount = (float) $order->refunded_amount + $totalRefundAmount;
            $updateData = ['refunded_amount' => $newRefundedAmount];

            // Mark as Refunded if fully refunded
            if ($newRefundedAmount >= (float) $order->total) {
                $updateData['status'] = OrderStatus::Refunded;
            }

            $order->update($updateData);

            // Restore inventory for refunded items
            $this->incrementForRefund($order, $refundItems, $userId);

            $refund = $refund->load(['items.orderItem', 'creator:id,name']);

            OrderRefunded::dispatch($refund, $order, $tenant);

            return $refund;
        });
    }

    private function incrementForRefund(Order $order, array $refundItems, int $userId): void
    {
        if (! $order->branch_id) {
            return;
        }

        foreach ($refundItems as $ri) {
            $orderItem = $order->items->firstWhere('id', $ri['order_item_id']);
            if (! $orderItem || ! $orderItem->product_id) {
                continue;
            }

            $product = Product::find($orderItem->product_id);
            if ($product && $product->is_food) {
                continue;
            }

            $inventory = Inventory::where('product_id', $orderItem->product_id)
                ->where('branch_id', $order->branch_id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                continue;
            }

            $quantityBefore = $inventory->quantity_on_hand;
            $quantityAfter = $quantityBefore + $ri['quantity'];

            $inventory->update(['quantity_on_hand' => $quantityAfter]);

            InventoryAdjustment::create([
                'tenant_id' => $order->tenant_id,
                'inventory_id' => $inventory->id,
                'type' => AdjustmentType::Refund,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'quantity_change' => $ri['quantity'],
                'reason' => "Refund for order {$order->order_number}",
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'created_by' => $userId,
            ]);
        }
    }

    private function generateRefundNumber(Tenant $tenant): string
    {
        $today = now()->format('Ymd');
        $prefix = "REF-{$today}-";

        $lastRefund = Refund::forTenant($tenant)
            ->where('refund_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderByDesc('refund_number')
            ->first();

        if ($lastRefund) {
            $lastNumber = (int) str_replace($prefix, '', $lastRefund->refund_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
