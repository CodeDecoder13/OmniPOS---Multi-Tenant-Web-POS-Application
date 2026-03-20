<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SupplierRequest;
use App\Services\Tenant\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/suppliers/Index', [
            'suppliers' => $this->supplierService->list($tenant, $request),
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    public function create(Request $request, string $tenantSlug): Response
    {
        return Inertia::render('tenant/suppliers/Create');
    }

    public function store(SupplierRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $this->supplierService->create($tenant, $request->validated(), $request->user()->id);

        return redirect()
            ->route('tenant.suppliers.index', ['tenant' => $tenant->slug])
            ->with('success', 'Supplier created successfully.');
    }

    public function edit(Request $request, string $tenantSlug, int $supplier): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $supplier = $this->supplierService->findForTenant($tenant, $supplier);

        return Inertia::render('tenant/suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(SupplierRequest $request, string $tenantSlug, int $supplier): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $supplier = $this->supplierService->findForTenant($tenant, $supplier);

        $this->supplierService->update($supplier, $request->validated());

        return redirect()
            ->route('tenant.suppliers.index', ['tenant' => $tenant->slug])
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Request $request, string $tenantSlug, int $supplier): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $supplier = $this->supplierService->findForTenant($tenant, $supplier);

        try {
            $this->supplierService->delete($supplier);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.suppliers.index', ['tenant' => $tenant->slug])
            ->with('success', 'Supplier deleted successfully.');
    }
}
