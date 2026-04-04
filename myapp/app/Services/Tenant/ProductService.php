<?php

namespace App\Services\Tenant;

use App\Enums\AdjustmentType;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Inventory;
use App\Models\Tenant\Product;
use App\Services\PlanLimitService;
use App\Models\Tenant\VariationGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}
    private function diskName(): string
    {
        return config('filesystems.product_disk', 'local');
    }

    private function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->diskName());
    }

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

    public function create(Tenant $tenant, array $data, int $userId, ?UploadedFile $image = null, ?int $initialStock = null, ?int $branchId = null): Product
    {
        PlanLimitService::ensureWithinLimit($tenant, 'products');

        $product = Product::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'created_by' => $userId,
        ]);

        if ($image) {
            $this->storeImage($product, $image);
        }

        if (!$product->is_food && $initialStock !== null && $branchId) {
            $inventory = Inventory::create([
                'tenant_id' => $tenant->id,
                'product_id' => $product->id,
                'branch_id' => $branchId,
                'quantity_on_hand' => 0,
                'low_stock_threshold' => 0,
            ]);

            if ($initialStock > 0) {
                $this->inventoryService->adjust($inventory, AdjustmentType::Initial, $initialStock, 'Initial stock on product creation', $userId);
            }
        }

        return $product;
    }

    public function update(Product $product, array $data, ?UploadedFile $image = null, bool $removeImage = false, ?int $initialStock = null, ?int $branchId = null): Product
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

        // Create initial inventory when toggling to non-food, only if no inventory exists yet
        if (!$product->is_food && $initialStock !== null && $initialStock > 0 && $branchId) {
            $exists = Inventory::where('product_id', $product->id)
                ->where('branch_id', $branchId)
                ->exists();

            if (!$exists) {
                $inventory = Inventory::create([
                    'tenant_id' => $product->tenant_id,
                    'product_id' => $product->id,
                    'branch_id' => $branchId,
                    'quantity_on_hand' => 0,
                    'low_stock_threshold' => 0,
                ]);

                $this->inventoryService->adjust($inventory, AdjustmentType::Initial, $initialStock, 'Initial stock on product update', $product->created_by);
            }
        }

        return $product->fresh(['category:id,name', 'creator:id,name']);
    }

    public function delete(Product $product): void
    {
        $this->deleteImage($product);
        $product->delete();
    }

    private function storeImage(Product $product, UploadedFile $image): void
    {
        $path = $image->store("{$product->tenant_id}/products/{$product->id}", $this->diskName());
        $product->update(['image_path' => $path]);
    }

    private function deleteImage(Product $product): void
    {
        if ($product->image_path && $this->disk()->exists($product->image_path)) {
            $this->disk()->delete($product->image_path);
        }
    }

    public function syncVariations(Product $product, array $groups): void
    {
        DB::transaction(function () use ($product, $groups) {
            // Delete options before groups to avoid FK issues
            foreach ($product->variationGroups as $group) {
                $group->options()->delete();
            }
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
        });
    }

    public function syncAddons(Product $product, array $addonIds): void
    {
        $product->addons()->sync($addonIds);
    }

    public function syncBranchAvailability(Product $product, ?array $branchIds, Tenant $tenant): void
    {
        $activeBranchIds = Branch::forTenant($tenant)->where('is_active', true)->pluck('id')->all();

        // If all branches selected or null → global availability (no entries)
        if ($branchIds === null || count($branchIds) >= count($activeBranchIds)) {
            $product->branches()->detach();
            return;
        }

        // Sync: selected = available, unselected = unavailable
        $syncData = [];
        foreach ($activeBranchIds as $id) {
            $syncData[$id] = ['is_available' => in_array($id, $branchIds)];
        }
        $product->branches()->sync($syncData);
    }
}
