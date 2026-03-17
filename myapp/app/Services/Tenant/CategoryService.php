<?php

namespace App\Services\Tenant;

use App\Enums\BusinessType;
use App\Models\Tenant;
use App\Models\Tenant\Category;
use Illuminate\Database\Eloquent\Collection;

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
