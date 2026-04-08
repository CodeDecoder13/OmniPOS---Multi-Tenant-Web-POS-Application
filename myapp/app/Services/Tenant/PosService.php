<?php

namespace App\Services\Tenant;

use App\Enums\KitchenStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\TableStatus;
use App\Events\OrderCompleted;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\Promotion;
use App\Models\Tenant\Table;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}
    public function getProducts(Tenant $tenant, Request $request, ?int $branchId = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::forTenant($tenant)
            ->where('products.is_active', true)
            ->with('category:id,name');

        $query->with(['variationGroups.options' => function ($q) {
            $q->where('is_active', true);
        }, 'addons' => function ($q) {
            $q->where('is_active', true);
        }]);

        if ($branchId) {
            $query->leftJoin('branch_product', function ($join) use ($branchId) {
                $join->on('products.id', '=', 'branch_product.product_id')
                    ->where('branch_product.branch_id', '=', $branchId);
            })
            ->where(function ($q) {
                $q->whereNull('branch_product.id')
                  ->orWhere('branch_product.is_available', true);
            })
            ->select([
                'products.id',
                'products.name',
                'products.image_path',
                'products.category_id',
                'products.sku',
                DB::raw('COALESCE(branch_product.custom_price, products.price) as price'),
            ]);
        } else {
            $query->select(['products.id', 'products.name', 'products.price', 'products.image_path', 'products.category_id', 'products.sku']);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('products.category_id', $categoryId);
        }

        return $query->orderBy('products.name')->paginate($perPage)->withQueryString();
    }

    public function checkout(Tenant $tenant, array $data, int $userId, ?int $branchId = null): Order
    {
        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch) {
                if (! $branch->getSetting('pos_enabled')) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator: \Illuminate\Support\Facades\Validator::make([], []),
                        response: response()->json(['message' => 'POS is not enabled for this branch.'], 422)
                    );
                }
                if (! empty($data['discount_amount']) && ! $branch->getSetting('discounts_enabled')) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator: \Illuminate\Support\Facades\Validator::make([], []),
                        response: response()->json(['message' => 'Discounts are not enabled for this branch.'], 422)
                    );
                }
            }
        }

        return DB::transaction(function () use ($tenant, $data, $userId, $branchId) {
            // If recalling a held order, delete it first
            if (! empty($data['order_id'])) {
                $heldOrder = Order::forTenant($tenant)
                    ->where('id', $data['order_id'])
                    ->where('status', OrderStatus::Pending)
                    ->whereNotNull('held_at')
                    ->first();

                if ($heldOrder) {
                    $heldOrder->items()->delete();
                    $heldOrder->delete();
                }
            }

            $orderNumber = $this->generateOrderNumber($tenant);

            // Calculate totals from items
            $items = $data['items'];
            $productIds = collect($items)->pluck('product_id')->toArray();
            $products = Product::forTenant($tenant)
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            // Validate all products are active
            foreach ($products as $product) {
                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => "Product \"{$product->name}\" is no longer available.",
                    ]);
                }
            }

            // Load branch price overrides
            $branchPrices = [];
            if ($branchId) {
                $branchPrices = DB::table('branch_product')
                    ->where('branch_id', $branchId)
                    ->whereIn('product_id', $productIds)
                    ->whereNotNull('custom_price')
                    ->pluck('custom_price', 'product_id')
                    ->toArray();
            }

            $subtotal = 0;
            $orderItems = [];

            $itemVariations = [];
            $itemAddons = [];

            foreach ($items as $index => $item) {
                $product = $products[$item['product_id']];
                $effectivePrice = $branchPrices[$product->id] ?? $product->price;

                // Calculate variation price modifiers
                $variationTotal = 0;
                if (!empty($item['variations'])) {
                    foreach ($item['variations'] as $variation) {
                        $variationTotal += $variation['price_modifier'] ?? 0;
                    }
                    $itemVariations[$index] = $item['variations'];
                }

                // Calculate addon prices
                $addonTotal = 0;
                if (!empty($item['addons'])) {
                    foreach ($item['addons'] as $addon) {
                        $addonTotal += $addon['addon_price'] ?? 0;
                    }
                    $itemAddons[$index] = $item['addons'];
                }

                $unitPrice = $effectivePrice + $variationTotal + $addonTotal;
                $itemSubtotal = $unitPrice * $item['quantity'];
                $subtotal += $itemSubtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_price' => $unitPrice,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                    'notes' => $item['notes'] ?? null,
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

            // Apply promotion discount
            $promotionId = $data['promotion_id'] ?? null;
            $promotionDiscount = 0;

            if ($promotionId) {
                $promotion = Promotion::forTenant($tenant)->where('id', $promotionId)->lockForUpdate()->first();
                if ($promotion && $promotion->isValid($afterDiscount)) {
                    if ($promotion->type->value === 'percentage') {
                        $promotionDiscount = round($afterDiscount * (floatval($promotion->value) / 100), 2);
                    } else {
                        $promotionDiscount = floatval($promotion->value);
                    }

                    if ($promotion->max_discount && $promotionDiscount > floatval($promotion->max_discount)) {
                        $promotionDiscount = floatval($promotion->max_discount);
                    }

                    $afterDiscount = max(0, $afterDiscount - $promotionDiscount);
                    $promotion->increment('used_count');
                } else {
                    $promotionId = null;
                }
            }

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

            // Validate payments total covers order total
            $payments = $data['payments'] ?? [];
            $paymentSum = collect($payments)->sum('amount');

            // For cash-only single payment, validate tendered >= total
            if (count($payments) === 1 && ($payments[0]['method'] ?? '') === 'cash') {
                if ($paymentSum < $total) {
                    throw ValidationException::withMessages([
                        'payments' => "Amount tendered ({$paymentSum}) is less than the total ({$total}).",
                    ]);
                }
            } elseif ($paymentSum < $total) {
                throw ValidationException::withMessages([
                    'payments' => "Total payments ({$paymentSum}) must cover the order total ({$total}).",
                ]);
            }

            // Create order
            $tableId = $data['table_id'] ?? null;

            $order = Order::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branchId,
                'customer_id' => $data['customer_id'] ?? null,
                'order_number' => $orderNumber,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_type' => $discountType,
                'promotion_id' => $promotionId,
                'promotion_discount' => $promotionDiscount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'order_type' => $data['order_type'],
                'status' => OrderStatus::Completed,
                'created_by' => $userId,
                'shift_id' => $data['shift_id'] ?? null,
                'table_id' => $tableId,
                'receipt_token' => Str::random(64),
            ]);

            // Mark table as occupied
            if ($tableId) {
                $table = Table::find($tableId);
                if ($table) {
                    $table->update(['status' => TableStatus::Occupied]);
                }
            }

            // Create order items
            $createdItems = [];
            foreach ($orderItems as $oi) {
                $createdItems[] = $order->items()->create($oi);
            }

            // Store variation/addon snapshots
            foreach ($createdItems as $index => $createdItem) {
                if (!empty($itemVariations[$index])) {
                    foreach ($itemVariations[$index] as $v) {
                        $createdItem->variations()->create([
                            'variation_group_name' => $v['variation_group_name'],
                            'option_name' => $v['option_name'],
                            'price_modifier' => $v['price_modifier'] ?? 0,
                        ]);
                    }
                }
                if (!empty($itemAddons[$index])) {
                    foreach ($itemAddons[$index] as $a) {
                        $createdItem->itemAddons()->create([
                            'addon_name' => $a['addon_name'],
                            'addon_price' => $a['addon_price'] ?? 0,
                        ]);
                    }
                }
            }

            // Send to kitchen if enabled
            if ($branchId) {
                $branch = Branch::find($branchId);
                if ($branch && $branch->getSetting('kitchen_display')) {
                    $order->update([
                        'kitchen_status' => KitchenStatus::New,
                        'kitchen_sent_at' => now(),
                    ]);
                }
            }

            // Decrement inventory
            $this->inventoryService->decrementForSale($tenant, $branchId, $items, $order->id, $userId);

            // Create payments (split payment support)
            foreach ($payments as $paymentData) {
                $method = $paymentData['method'];
                $amount = (float) $paymentData['amount'];
                $isCash = $method === 'cash';
                $changeAmount = 0;

                // For the last cash payment, calculate change
                if ($isCash && count($payments) === 1) {
                    $changeAmount = max(0, $amount - $total);
                }

                $order->payments()->create([
                    'amount' => $isCash && count($payments) === 1 ? $total : $amount,
                    'method' => $method,
                    'reference_number' => $paymentData['reference_number'] ?? null,
                    'status' => PaymentStatus::Completed,
                    'amount_tendered' => $isCash ? $amount : null,
                    'change_amount' => $changeAmount,
                ]);
            }

            $order = $order->load(['items.variations', 'items.itemAddons', 'payments', 'customer:id,name', 'table:id,name', 'promotion:id,code,name']);

            OrderCompleted::dispatch($order, $tenant);

            return $order;
        });
    }

    private function generateOrderNumber(Tenant $tenant): string
    {
        $today = now()->format('Ymd');
        $prefix = "ORD-{$today}-";

        $lastOrder = Order::forTenant($tenant)
            ->where('order_number', 'like', "{$prefix}%")
            ->lockForUpdate()
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
