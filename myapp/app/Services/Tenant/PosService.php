<?php

namespace App\Services\Tenant;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}
    public function getProducts(Tenant $tenant, Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::forTenant($tenant)
            ->where('is_active', true)
            ->with('category:id,name')
            ->select(['id', 'name', 'price', 'image_path', 'category_id', 'sku']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        return $query->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function checkout(Tenant $tenant, array $data, int $userId, ?int $branchId = null): Order
    {
        return DB::transaction(function () use ($tenant, $data, $userId, $branchId) {
            $orderNumber = $this->generateOrderNumber($tenant);

            // Calculate totals from items
            $items = $data['items'];
            $productIds = collect($items)->pluck('product_id')->toArray();
            $products = Product::forTenant($tenant)
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $product = $products[$item['product_id']];
                $itemSubtotal = $product->price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                ];
            }

            // Calculate discount
            $discountAmount = 0;
            $discountType = $data['discount_type'] ?? null;

            if (! empty($data['discount_amount']) && $discountType) {
                if ($discountType === 'percentage') {
                    $discountAmount = round($subtotal * ($data['discount_amount'] / 100), 2);
                } else {
                    $discountAmount = $data['discount_amount'];
                }
            }

            $afterDiscount = $subtotal - $discountAmount;

            // Calculate tax
            $taxRate = $tenant->data['tax_rate'] ?? 0;
            $taxInclusive = $tenant->data['tax_inclusive'] ?? false;
            $taxAmount = 0;

            if ($taxRate > 0) {
                if ($taxInclusive) {
                    $taxAmount = round($afterDiscount - ($afterDiscount / (1 + $taxRate / 100)), 2);
                    $total = $afterDiscount;
                } else {
                    $taxAmount = round($afterDiscount * ($taxRate / 100), 2);
                    $total = $afterDiscount + $taxAmount;
                }
            } else {
                $total = $afterDiscount;
            }

            // Create order
            $order = Order::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branchId,
                'customer_id' => $data['customer_id'] ?? null,
                'order_number' => $orderNumber,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_type' => $discountType,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'order_type' => $data['order_type'],
                'status' => OrderStatus::Completed,
                'created_by' => $userId,
                'shift_id' => $data['shift_id'] ?? null,
            ]);

            // Create order items
            $order->items()->createMany($orderItems);

            // Decrement inventory
            $this->inventoryService->decrementForSale($tenant, $branchId, $items, $order->id, $userId);

            // Create payment
            $paymentMethod = $data['payment_method'];
            $amountTendered = $data['amount_tendered'] ?? $total;
            $changeAmount = $paymentMethod === 'cash' ? max(0, $amountTendered - $total) : 0;

            $order->payments()->create([
                'amount' => $total,
                'method' => $paymentMethod,
                'reference_number' => $data['reference_number'] ?? null,
                'status' => PaymentStatus::Completed,
                'amount_tendered' => $amountTendered,
                'change_amount' => $changeAmount,
            ]);

            return $order->load(['items', 'payments', 'customer:id,name']);
        });
    }

    private function generateOrderNumber(Tenant $tenant): string
    {
        $today = now()->format('Ymd');
        $prefix = "ORD-{$today}-";

        $lastOrder = Order::forTenant($tenant)
            ->where('order_number', 'like', "{$prefix}%")
            ->orderByDesc('order_number')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) str_replace($prefix, '', $lastOrder->order_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
