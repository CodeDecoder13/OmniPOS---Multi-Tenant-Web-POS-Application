<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\RefundRequest;
use App\Services\Tenant\OrderService;
use App\Services\Tenant\ReceiptService;
use App\Services\Tenant\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly ReceiptService $receiptService,
        private readonly RefundService $refundService,
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

    public function refundPage(Request $request, string $tenantSlug, int $order): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $order = $this->orderService->findForTenant($tenant, $order, $tenantUser->branch_id ?? null);

        if (! $order->canBeRefunded()) {
            return redirect()->route('tenant.orders.show', ['tenant' => $tenant->slug, 'order' => $order->id])
                ->with('error', 'This order cannot be refunded.');
        }

        return Inertia::render('tenant/orders/Refund', [
            'order' => $order,
        ]);
    }

    public function refund(RefundRequest $request, string $tenantSlug, int $order): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $order = $this->orderService->findForTenant($tenant, $order, $tenantUser->branch_id ?? null);

        $this->refundService->processRefund($tenant, $order, $request->validated(), $request->user()->id);

        return redirect()->route('tenant.orders.show', ['tenant' => $tenant->slug, 'order' => $order->id])
            ->with('success', 'Refund processed successfully.');
    }

    public function receiptPdf(Request $request, string $tenantSlug, int $order): \Illuminate\Http\Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $order = $this->orderService->findForTenant($tenant, $order, $tenantUser->branch_id ?? null);

        return $this->receiptService->generatePdf($tenant, $order);
    }

    public function emailReceipt(Request $request, string $tenantSlug, int $order): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $order = $this->orderService->findForTenant($tenant, $order, $tenantUser->branch_id ?? null);

        $this->receiptService->emailReceipt($tenant, $order, $request->input('email'));

        return response()->json(['success' => true]);
    }

    public function receiptLink(Request $request, string $tenantSlug, int $order): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $order = $this->orderService->findForTenant($tenant, $order, $tenantUser->branch_id ?? null);

        return response()->json([
            'url' => $this->receiptService->getShareableUrl($order),
        ]);
    }
}
