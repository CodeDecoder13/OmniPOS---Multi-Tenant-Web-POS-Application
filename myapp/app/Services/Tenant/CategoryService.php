<?php

namespace App\Services\Tenant;

use App\Enums\BusinessType;
use App\Models\Tenant;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\TransferStatus;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\StockTransfer;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Category;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\PurchaseOrderItem;
use App\Models\Tenant\StockTransferItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        // Open purchase orders and transfers are work in progress; wiping their lines would corrupt it.
        // Finished ones are history and are cleared along with the products (the modal says so).
        $openPurchaseOrders = PurchaseOrder::forTenant($tenant)
            ->whereIn('status', [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Sent, PurchaseOrderStatus::Partial])
            ->count();
        $openTransfers = StockTransfer::forTenant($tenant)
            ->whereIn('status', [TransferStatus::Pending, TransferStatus::InTransit])
            ->count();

        if ($openPurchaseOrders + $openTransfers > 0) {
            throw new \DomainException("You still have {$openPurchaseOrders} open purchase order(s) and {$openTransfers} open stock transfer(s). Receive, complete or cancel them first, then change the business type.");
        }

        $imagePaths = Product::forTenant($tenant)->whereNotNull('image_path')->pluck('image_path')->all();

        DB::transaction(function () use ($tenant, $businessType) {
            $productIds = Product::forTenant($tenant)->select('id');

            // Held (parked) orders would point at products that no longer exist and could not be recalled.
            $held = Order::forTenant($tenant)->where('status', OrderStatus::Pending)->whereNotNull('held_at')->get();
            foreach ($held as $order) {
                $order->items()->delete();
                $order->delete();
            }

            // These FKs have no cascade, so they would block the product delete.
            PurchaseOrderItem::whereIn('product_id', $productIds)->delete();
            StockTransferItem::whereIn('product_id', $productIds)->delete();

            // Cascades to inventory, branch pricing, variations, add-on and supplier links.
            Product::forTenant($tenant)->delete();
            Category::forTenant($tenant)->delete();

            $tenant->update(['business_type' => $businessType]);
            $this->seedDefaults($tenant, $businessType);

            // Offer the new type's recommended switches, but never override a choice the owner already made:
            // existing values win, so only switches that were never set get turned on.
            $recommended = $businessType->branchSettings();
            if ($recommended !== []) {
                foreach (Branch::forTenant($tenant)->get() as $branch) {
                    $branch->update(['settings' => array_merge($recommended, $branch->settings ?? [])]);
                }
            }
        });

        // Photos go last, after the data change committed, so a failure never leaves rows pointing at missing files.
        $disk = Storage::disk(config('filesystems.product_disk', 'local'));
        foreach ($imagePaths as $path) {
            $disk->delete($path);
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
