<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ProductRequest;
use App\Models\Tenant\Addon;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Services\Tenant\ProductService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/products/Index', [
            'products' => $this->productService->list($tenant, $request),
            'categories' => Category::forTenant($tenant)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => $request->only(['search', 'category_id', 'is_active']),
            'productsCount' => Product::where('tenant_id', $tenant->id)->count(),
        ]);
    }

    public function create(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/products/Create', [
            'categories' => Category::forTenant($tenant)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'addons' => Addon::forTenant($tenant)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'price']),
        ]);
    }

    public function store(ProductRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $data = $request->safe()->except(['image', 'remove_image', 'variation_groups', 'addon_ids']);

        try {
            $product = $this->productService->create($tenant, $data, $request->user()->id, $request->file('image'));
        } catch (DomainException $e) {
            return redirect()
                ->route('tenant.products.index', ['tenant' => $tenant->slug])
                ->with('error', $e->getMessage());
        }

        if ($request->has('variation_groups') && is_array($request->input('variation_groups'))) {
            $this->productService->syncVariations($product, $request->input('variation_groups'));
        }

        if ($request->has('addon_ids')) {
            $this->productService->syncAddons($product, $request->input('addon_ids', []));
        }

        return redirect()
            ->route('tenant.products.index', ['tenant' => $tenant->slug])
            ->with('success', 'Product created successfully.');
    }

    public function edit(Request $request, string $tenantSlug, int $product): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $product = $this->productService->findForTenant($tenant, $product);
        $product->load(['variationGroups.options', 'addons:id']);

        return Inertia::render('tenant/products/Edit', [
            'product' => $product,
            'categories' => Category::forTenant($tenant)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'addons' => Addon::forTenant($tenant)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'price']),
        ]);
    }

    public function update(ProductRequest $request, string $tenantSlug, int $product): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $product = $this->productService->findForTenant($tenant, $product);

        $data = $request->safe()->except(['image', 'remove_image', 'variation_groups', 'addon_ids']);

        $this->productService->update(
            $product,
            $data,
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        if ($request->has('variation_groups')) {
            $this->productService->syncVariations($product, $request->input('variation_groups', []));
        }

        if ($request->has('addon_ids')) {
            $this->productService->syncAddons($product, $request->input('addon_ids', []));
        }

        return redirect()
            ->route('tenant.products.index', ['tenant' => $tenant->slug])
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, string $tenantSlug, int $product): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $product = $this->productService->findForTenant($tenant, $product);

        $this->productService->delete($product);

        return redirect()
            ->route('tenant.products.index', ['tenant' => $tenant->slug])
            ->with('success', 'Product deleted successfully.');
    }
}
