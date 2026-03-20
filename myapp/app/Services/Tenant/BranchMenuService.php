<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BranchMenuService
{
    public function getMenuForBranch(Tenant $tenant, Branch $branch, ?string $search = null, ?int $categoryId = null): Collection
    {
        $query = Product::forTenant($tenant)
            ->where('is_active', true)
            ->with('category:id,name')
            ->leftJoin('branch_product', function ($join) use ($branch) {
                $join->on('products.id', '=', 'branch_product.product_id')
                    ->where('branch_product.branch_id', '=', $branch->id);
            })
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.price',
                'products.category_id',
                'products.image_path',
                DB::raw('COALESCE(branch_product.custom_price, products.price) as effective_price'),
                DB::raw('COALESCE(branch_product.is_available, true) as branch_available'),
                'branch_product.custom_price',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        return $query->orderBy('products.name')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'category' => $product->category,
                'image_url' => $product->image_path ? '/storage/' . $product->image_path : null,
                'effective_price' => $product->effective_price,
                'custom_price' => $product->custom_price,
                'is_available' => (bool) $product->branch_available,
            ];
        });
    }

    public function syncBranchProducts(Branch $branch, array $items): void
    {
        $syncData = [];

        foreach ($items as $item) {
            $hasOverride = $item['custom_price'] !== null || $item['is_available'] === false;

            if ($hasOverride) {
                $syncData[$item['product_id']] = [
                    'custom_price' => $item['custom_price'],
                    'is_available' => $item['is_available'],
                ];
            }
        }

        $branch->products()->sync($syncData);
    }

    public function getBranchProductOverrides(Branch $branch): Collection
    {
        return $branch->products()->get()->map(function ($product) {
            return [
                'product_id' => $product->id,
                'custom_price' => $product->pivot->custom_price,
                'is_available' => (bool) $product->pivot->is_available,
            ];
        });
    }
}
