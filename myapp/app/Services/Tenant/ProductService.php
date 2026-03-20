<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\Product;
use App\Models\Tenant\VariationGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function list(Tenant $tenant, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::forTenant($tenant)
            ->with(['category:id,name', 'creator:id,name']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    public function findForTenant(Tenant $tenant, int $productId): Product
    {
        return Product::forTenant($tenant)
            ->with(['category:id,name', 'creator:id,name'])
            ->findOrFail($productId);
    }

    public function create(Tenant $tenant, array $data, int $userId, ?UploadedFile $image = null): Product
    {
        $product = Product::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'created_by' => $userId,
        ]);

        if ($image) {
            $this->storeImage($product, $image);
        }

        return $product;
    }

    public function update(Product $product, array $data, ?UploadedFile $image = null, bool $removeImage = false): Product
    {
        if ($removeImage && !$image) {
            $this->deleteImage($product);
            $data['image_path'] = null;
        }

        if ($image) {
            $this->deleteImage($product);
            $this->storeImage($product, $image);
        }

        $product->update($data);

        return $product->fresh(['category:id,name', 'creator:id,name']);
    }

    public function delete(Product $product): void
    {
        $this->deleteImage($product);
        $product->delete();
    }

    private function storeImage(Product $product, UploadedFile $image): void
    {
        $path = $image->store("products/{$product->id}", 'public');
        $product->update(['image_path' => $path]);
    }

    private function deleteImage(Product $product): void
    {
        if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
            Storage::disk('public')->deleteDirectory("products/{$product->id}");
        }
    }

    public function syncVariations(Product $product, array $groups): void
    {
        $product->variationGroups()->delete();

        foreach ($groups as $index => $group) {
            $vg = $product->variationGroups()->create([
                'tenant_id' => $product->tenant_id,
                'name' => $group['name'],
                'sort_order' => $index,
                'is_required' => $group['is_required'] ?? false,
            ]);

            foreach ($group['options'] as $optIndex => $option) {
                $vg->options()->create([
                    'name' => $option['name'],
                    'price_modifier' => $option['price_modifier'] ?? 0,
                    'sort_order' => $optIndex,
                ]);
            }
        }
    }

    public function syncAddons(Product $product, array $addonIds): void
    {
        $product->addons()->sync($addonIds);
    }
}
