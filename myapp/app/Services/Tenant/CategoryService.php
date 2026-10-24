<?php

namespace App\Services\Tenant;

use App\Enums\BusinessType;
use App\Models\Tenant;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\PurchaseOrderItem;
use App\Models\Tenant\StockTransferItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function seedDefaults(Tenant $tenant, BusinessType $businessType): void
    {
        foreach ($businessType->defaultCategories() as $index => $categoryData) {
            Category::create([
                'tenant_id' => $tenant->id,
                'name' => $categoryData['name'],
                'slug' => $categoryData['slug'],
                'description' => $categoryData['description'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * Switch a tenant's business type: wipes all products and categories
     * (and the records that hard-reference them), then seeds the new defaults.
     * Order history is kept; order items just lose their product link.
     */
    public function resetForBusinessType(Tenant $tenant, BusinessType $businessType): void
    {
        DB::transaction(function () use ($tenant, $businessType) {
            $productIds = Product::forTenant($tenant)->select('id');

            // These FKs have no cascade, so they would block the product delete.
            PurchaseOrderItem::whereIn('product_id', $productIds)->delete();
            StockTransferItem::whereIn('product_id', $productIds)->delete();

            // Cascades to inventory, branch pricing, variations, add-on and supplier links.
            Product::forTenant($tenant)->delete();
            Category::forTenant($tenant)->delete();

            $tenant->update(['business_type' => $businessType]);
            $this->seedDefaults($tenant, $businessType);
        });
    }

    public function list(Tenant $tenant): Collection
    {
        return Category::forTenant($tenant)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function findForTenant(Tenant $tenant, int $categoryId): Category
    {
        return Category::forTenant($tenant)->findOrFail($categoryId);
    }

    public function create(Tenant $tenant, array $data): Category
    {
        return Category::create([
            ...$data,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        if ($category->products()->exists()) {
            throw new \RuntimeException('Cannot delete a category that has products.');
        }

        $category->delete();
    }
}
