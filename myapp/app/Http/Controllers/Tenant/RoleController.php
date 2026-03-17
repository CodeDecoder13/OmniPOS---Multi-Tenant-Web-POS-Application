<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\RoleRequest;
use App\Services\Tenant\RoleService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $roles = $this->roleService->list($tenant);

        return Inertia::render('tenant/roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create(Request $request, string $tenantSlug): Response
    {
        return Inertia::render('tenant/roles/Create', [
            'groupedPermissions' => $this->roleService->getGroupedPermissions(),
        ]);
    }

    public function store(RoleRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $this->roleService->create($tenant, $request->validated(), $request->validated('permissions'));

        return redirect()->route('tenant.roles.index', $tenantSlug)
            ->with('success', 'Role created successfully.');
    }

    public function edit(Request $request, string $tenantSlug, int $role): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $role = $this->roleService->find($tenant, $role);

        return Inertia::render('tenant/roles/Edit', [
            'role' => $role,
            'groupedPermissions' => $this->roleService->getGroupedPermissions(),
        ]);
    }

    public function update(RoleRequest $request, string $tenantSlug, int $role): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $roleModel = $this->roleService->find($tenant, $role);
        $this->roleService->update($roleModel, $request->validated(), $request->validated('permissions'));

        return redirect()->route('tenant.roles.index', $tenantSlug)
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, string $tenantSlug, int $role): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $roleModel = $this->roleService->find($tenant, $role);

        try {
            $this->roleService->delete($roleModel);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('tenant.roles.index', $tenantSlug)
            ->with('success', 'Role deleted successfully.');
    }
}
