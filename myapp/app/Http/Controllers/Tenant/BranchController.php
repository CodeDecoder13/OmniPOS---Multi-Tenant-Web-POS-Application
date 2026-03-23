<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BranchRequest;
use App\Services\Tenant\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function __construct(
        private readonly BranchService $branchService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/branches/Index', [
            'branches' => $this->branchService->list($tenant),
        ]);
    }

    public function create(Request $request, string $tenantSlug): Response
    {
        return Inertia::render('tenant/branches/Create');
    }

    public function store(BranchRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $this->branchService->create($tenant, $request->validated(), $request->user()->id);

        return redirect()
            ->route('tenant.branches.index', ['tenant' => $tenant->slug])
            ->with('success', 'Branch created successfully.');
    }

    public function edit(Request $request, string $tenantSlug, int $branch): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $branch = $this->branchService->findForTenant($tenant, $branch);

        return Inertia::render('tenant/branches/Edit', [
            'branch' => $branch,
        ]);
    }

    public function update(BranchRequest $request, string $tenantSlug, int $branch): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $branch = $this->branchService->findForTenant($tenant, $branch);

        $this->branchService->update($branch, $request->validated());

        return redirect()
            ->route('tenant.branches.index', ['tenant' => $tenant->slug])
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(Request $request, string $tenantSlug, int $branch): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $branch = $this->branchService->findForTenant($tenant, $branch);

        try {
            $this->branchService->delete($branch);
        } catch (\DomainException $e) {
            return redirect()
                ->route('tenant.branches.index', ['tenant' => $tenant->slug])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.branches.index', ['tenant' => $tenant->slug])
            ->with('success', 'Branch deleted successfully.');
    }
}
