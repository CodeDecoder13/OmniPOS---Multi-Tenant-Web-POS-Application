<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CustomerRequest;
use App\Services\Tenant\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/customers/Index', [
            'customers' => $this->customerService->list($tenant, $request),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tenant/customers/Create');
    }

    public function store(CustomerRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $this->customerService->create($tenant, $request->validated(), $request->user()->id);

        return redirect()->route('tenant.customers.index', ['tenant' => $tenant->slug])
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Request $request, string $tenantSlug, int $customer): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $customer = $this->customerService->findForTenant($tenant, $customer);

        return Inertia::render('tenant/customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(CustomerRequest $request, string $tenantSlug, int $customer): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $customer = $this->customerService->findForTenant($tenant, $customer);
        $this->customerService->update($customer, $request->validated());

        return redirect()->route('tenant.customers.index', ['tenant' => $tenant->slug])
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, string $tenantSlug, int $customer): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $customer = $this->customerService->findForTenant($tenant, $customer);
        $this->customerService->delete($customer);

        return redirect()->route('tenant.customers.index', ['tenant' => $tenant->slug])
            ->with('success', 'Customer deleted successfully.');
    }
}
