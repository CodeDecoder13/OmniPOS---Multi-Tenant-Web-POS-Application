<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Services\Tenant\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchSettingsController extends Controller
{
    public function __construct(
        private readonly BranchService $branchService,
    ) {}

    public function edit(Request $request, string $tenantSlug, int $branch): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $branch = $this->branchService->findForTenant($tenant, $branch);

        return Inertia::render('tenant/branches/Settings', [
            'branch' => $branch,
            'settings' => $branch->getSettings(),
        ]);
    }

    public function update(Request $request, string $tenantSlug, int $branch): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $branch = $this->branchService->findForTenant($tenant, $branch);

        $allowedKeys = array_keys(Branch::DEFAULT_SETTINGS);

        $validated = $request->validate(
            collect($allowedKeys)->mapWithKeys(fn ($key) => [$key => 'required|boolean'])->toArray()
        );

        $this->branchService->updateSettings($branch, $validated);

        return redirect()
            ->route('tenant.branches.settings', ['tenant' => $tenant->slug, 'branch' => $branch->id])
            ->with('success', 'Branch settings updated successfully.');
    }
}
