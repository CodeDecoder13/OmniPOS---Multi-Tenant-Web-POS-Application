<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Services\Tenant\BranchMenuService;
use App\Services\Tenant\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchMenuController extends Controller
{
    public function __construct(
        private readonly BranchMenuService $branchMenuService,
        private readonly BranchService $branchService,
    ) {}

    public function index(Request $request, string $tenantSlug, int $branch): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $branch = $this->branchService->findForTenant($tenant, $branch);

        $products = $this->branchMenuService->getMenuForBranch(
            $tenant,
            $branch,
            $request->input('search'),
            $request->input('category_id') ? (int) $request->input('category_id') : null,
        );

        $categories = Category::forTenant($tenant)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('tenant/branches/Menu', [
            'branch' => $branch,
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, string $tenantSlug, int $branch): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $branch = $this->branchService->findForTenant($tenant, $branch);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.custom_price' => 'nullable|numeric|min:0',
            'items.*.is_available' => 'required|boolean',
        ]);

        $this->branchMenuService->syncBranchProducts($branch, $validated['items']);

        return redirect()
            ->route('tenant.branches.menu', ['tenant' => $tenant->slug, 'branch' => $branch->id])
            ->with('success', 'Branch menu updated successfully.');
    }
}
