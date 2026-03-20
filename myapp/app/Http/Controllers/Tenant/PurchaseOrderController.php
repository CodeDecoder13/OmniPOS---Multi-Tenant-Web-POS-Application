<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PurchaseOrderRequest;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Product;
use App\Models\Tenant\Supplier;
use App\Services\Tenant\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/purchase-orders/Index', [
            'purchaseOrders' => $this->purchaseOrderService->list($tenant, $request),
            'suppliers' => Supplier::forTenant($tenant)->where('is_active', true)->get(['id', 'name']),
            'branches' => Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'supplier_id', 'branch_id']),
        ]);
    }

    public function create(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/purchase-orders/Create', [
            'suppliers' => Supplier::forTenant($tenant)->where('is_active', true)->get(['id', 'name']),
            'branches' => Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get(['id', 'name']),
            'products' => Product::forTenant($tenant)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'cost_price']),
        ]);
    }

    public function store(PurchaseOrderRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $po = $this->purchaseOrderService->create($tenant, $request->validated(), $request->user()->id);

        return redirect()
            ->route('tenant.purchase-orders.show', ['tenant' => $tenant->slug, 'purchase_order' => $po->id])
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(Request $request, string $tenantSlug, int $purchaseOrder): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $po = $this->purchaseOrderService->findForTenant($tenant, $purchaseOrder);

        return Inertia::render('tenant/purchase-orders/Show', [
            'purchaseOrder' => $po,
        ]);
    }

    public function edit(Request $request, string $tenantSlug, int $purchaseOrder): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $po = $this->purchaseOrderService->findForTenant($tenant, $purchaseOrder);

        return Inertia::render('tenant/purchase-orders/Edit', [
            'purchaseOrder' => $po,
            'suppliers' => Supplier::forTenant($tenant)->where('is_active', true)->get(['id', 'name']),
            'branches' => Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get(['id', 'name']),
            'products' => Product::forTenant($tenant)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'cost_price']),
        ]);
    }

    public function update(PurchaseOrderRequest $request, string $tenantSlug, int $purchaseOrder): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $po = $this->purchaseOrderService->findForTenant($tenant, $purchaseOrder);

        $this->purchaseOrderService->update($po, $request->validated());

        return redirect()
            ->route('tenant.purchase-orders.show', ['tenant' => $tenant->slug, 'purchase_order' => $po->id])
            ->with('success', 'Purchase order updated successfully.');
    }

    public function send(Request $request, string $tenantSlug, int $purchaseOrder): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $po = $this->purchaseOrderService->findForTenant($tenant, $purchaseOrder);

        $this->purchaseOrderService->send($po);

        return back()->with('success', 'Purchase order sent to supplier.');
    }

    public function receive(Request $request, string $tenantSlug, int $purchaseOrder): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $po = $this->purchaseOrderService->findForTenant($tenant, $purchaseOrder);

        $receivedQuantities = $request->input('received_quantities', []);
        $this->purchaseOrderService->receive($po, $receivedQuantities, $request->user()->id);

        return back()->with('success', 'Items received successfully.');
    }

    public function cancel(Request $request, string $tenantSlug, int $purchaseOrder): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $po = $this->purchaseOrderService->findForTenant($tenant, $purchaseOrder);

        $this->purchaseOrderService->cancel($po);

        return back()->with('success', 'Purchase order cancelled.');
    }
}
