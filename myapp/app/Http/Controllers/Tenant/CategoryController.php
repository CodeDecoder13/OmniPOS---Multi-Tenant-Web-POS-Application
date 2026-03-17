<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CategoryRequest;
use App\Services\Tenant\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/categories/Index', [
            'categories' => $this->categoryService->list($tenant),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tenant/categories/Create');
    }

    public function store(CategoryRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $this->categoryService->create($tenant, $request->validated());

        return redirect()
            ->route('tenant.categories.index', ['tenant' => $tenant->slug])
            ->with('success', 'Category created successfully.');
    }

    public function storeInline(CategoryRequest $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $category = $this->categoryService->create($tenant, $request->validated());

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
        ], 201);
    }

    public function edit(Request $request, string $tenantSlug, int $category): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $category = $this->categoryService->findForTenant($tenant, $category);

        return Inertia::render('tenant/categories/Edit', [
            'category' => $category,
        ]);
    }

    public function update(CategoryRequest $request, string $tenantSlug, int $category): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $category = $this->categoryService->findForTenant($tenant, $category);

        $this->categoryService->update($category, $request->validated());

        return redirect()
            ->route('tenant.categories.index', ['tenant' => $tenant->slug])
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Request $request, string $tenantSlug, int $category): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $category = $this->categoryService->findForTenant($tenant, $category);

        try {
            $this->categoryService->delete($category);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('tenant.categories.index', ['tenant' => $tenant->slug])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.categories.index', ['tenant' => $tenant->slug])
            ->with('success', 'Category deleted successfully.');
    }
}
