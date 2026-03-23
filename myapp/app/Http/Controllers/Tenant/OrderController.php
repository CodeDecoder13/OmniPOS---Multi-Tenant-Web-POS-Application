<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Tenant\OrderService;
use App\Services\Tenant\ReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly ReceiptService $receiptService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');

        return Inertia::render('tenant/orders/Index', [
            'orders' => $this->orderService->list($tenant, $request, $tenantUser->branch_id ?? null),
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function show(Request $request, string $tenantSlug, int $order): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $order = $this->orderService->findForTenant($tenant, $order, $tenantUser->branch_id ?? null);

        return Inertia::render('tenant/orders/Show', [
            'order' => $order,
        ]);
    }

    public function void(Request $request, string $tenantSlug, int $order): RedirectResponse
    {
        $request->validate([
            'void_reason' => ['required', 'string', 'max:1000'],
        ]);

        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $order = $this->orderService->findForTenant($tenant, $order, $tenantUser->branch_id ?? null);
        $this->orderService->voidOrder($order, $request->user()->id, $request->input('void_reason'));

        return redirect()->back()->with('success', 'Order has been voided.');
    }

    public function receiptPdf(Request $request, string $tenantSlug, int $order): \Illuminate\Http\Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $order = $this->orderService->findForTenant($tenant, $order, $tenantUser->branch_id ?? null);

        return $this->receiptService->generatePdf($tenant, $order);
    }
}
