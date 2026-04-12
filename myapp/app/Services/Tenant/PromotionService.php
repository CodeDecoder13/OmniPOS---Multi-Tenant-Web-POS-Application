<?php

namespace App\Services\Tenant;

use App\Enums\PromotionType;
use App\Models\Tenant;
use App\Models\Tenant\Promotion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PromotionService
{
    public function list(Tenant $tenant, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Promotion::forTenant($tenant);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function findForTenant(Tenant $tenant, int $promotionId): Promotion
    {
        return Promotion::forTenant($tenant)->findOrFail($promotionId);
    }

    public function create(Tenant $tenant, array $data): Promotion
    {
        return Promotion::create([
            ...$data,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function update(Promotion $promotion, array $data): Promotion
    {
        $promotion->update($data);
        return $promotion->fresh();
    }

    public function delete(Promotion $promotion): void
    {
        if ($promotion->orders()->exists()) {
            throw new \RuntimeException('Cannot delete promotion that has been used in orders.');
        }

        $promotion->delete();
    }

    public function applyPromoCode(Tenant $tenant, string $code, float $subtotal): array
    {
        $promotion = Promotion::forTenant($tenant)
            ->where('code', strtoupper($code))
            ->first();

        if (! $promotion) {
            return ['success' => false, 'message' => 'Promo code not found.'];
        }

        if (! $promotion->isValid($subtotal)) {
            if (! $promotion->is_active) {
                return ['success' => false, 'message' => 'This promo code is no longer active.'];
            }
            if ($promotion->usage_limit && $promotion->used_count >= $promotion->usage_limit) {
                return ['success' => false, 'message' => 'This promo code has reached its usage limit.'];
            }
            if ($promotion->min_order_amount && $subtotal < floatval($promotion->min_order_amount)) {
                return ['success' => false, 'message' => "Minimum order amount is {$promotion->min_order_amount}."];
            }
            return ['success' => false, 'message' => 'This promo code is not valid at this time.'];
        }

        // Calculate discount
        $discount = 0;
        if ($promotion->type->value === 'percentage') {
            $discount = round($subtotal * (floatval($promotion->value) / 100), 2);
        } else {
            $discount = floatval($promotion->value);
        }

        if ($promotion->max_discount && $discount > floatval($promotion->max_discount)) {
            $discount = floatval($promotion->max_discount);
        }

        return [
            'success' => true,
            'promotion' => [
                'id' => $promotion->id,
                'code' => $promotion->code,
                'name' => $promotion->name,
                'type' => $promotion->type->value,
                'value' => $promotion->value,
            ],
            'discount' => $discount,
        ];
    }

    public function getPresetDiscounts(Tenant $tenant): array
    {
        return Promotion::forTenant($tenant)
            ->where('is_preset', true)
            ->where('is_active', true)
            ->get()
            ->map(fn (Promotion $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'type' => $p->type->value,
                'value' => $p->value,
            ])
            ->toArray();
    }

    public function seedPresetDiscounts(Tenant $tenant): void
    {
        $presets = [
            [
                'code' => 'STUDENT20',
                'name' => 'Student Discount',
                'type' => PromotionType::Student,
                'value' => 20.00,
                'description' => 'Student discount - 20% off with valid student ID',
            ],
            [
                'code' => 'PWD20',
                'name' => 'PWD Discount',
                'type' => PromotionType::PWD,
                'value' => 20.00,
                'description' => 'Person with Disability discount - 20% off with valid PWD ID',
            ],
            [
                'code' => 'SENIOR20',
                'name' => 'Senior Citizen Discount',
                'type' => PromotionType::SeniorCitizen,
                'value' => 20.00,
                'description' => 'Senior Citizen discount - 20% off with valid Senior Citizen ID',
            ],
        ];

        foreach ($presets as $preset) {
            $exists = Promotion::forTenant($tenant)
                ->where('code', $preset['code'])
                ->exists();

            if (! $exists) {
                Promotion::create([
                    ...$preset,
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                    'is_preset' => true,
                ]);
            }
        }
    }
}
