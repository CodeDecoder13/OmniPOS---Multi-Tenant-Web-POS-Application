<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AddonRequest;
use App\Services\Tenant\AddonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AddonController extends Controller
{
    public function __construct(
        private readonly AddonService $addonService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/addons/Index', [
            'addons' => $this->addonService->list($tenant, $request),
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    public function create(Request $request, string $tenantSlug): Response
    {
        return Inertia::render('tenant/addons/Create');
    }

    public function store(AddonRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $this->addonService->create($tenant, $request->validated());

        return redirect()
            ->route('tenant.addons.index', ['tenant' => $tenant->slug])
            ->with('success', 'Add-on created successfully.');
    }

    public function edit(Request $request, string $tenantSlug, int $addon): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $addon = $this->addonService->findForTenant($tenant, $addon);

        return Inertia::render('tenant/addons/Edit', [
            'addon' => $addon,
        ]);
    }

    public function update(AddonRequest $request, string $tenantSlug, int $addon): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $addon = $this->addonService->findForTenant($tenant, $addon);

        $this->addonService->update($addon, $request->validated());

        return redirect()
            ->route('tenant.addons.index', ['tenant' => $tenant->slug])
            ->with('success', 'Add-on updated successfully.');
    }

    public function destroy(Request $request, string $tenantSlug, int $addon): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $addon = $this->addonService->findForTenant($tenant, $addon);

        $this->addonService->delete($addon);

        return redirect()
            ->route('tenant.addons.index', ['tenant' => $tenant->slug])
            ->with('success', 'Add-on deleted successfully.');
    }
}
