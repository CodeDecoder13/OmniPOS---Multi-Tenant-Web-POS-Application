<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StockTransferRequest;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Product;
use App\Services\Tenant\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferService $stockTransferService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/stock-transfers/Index', [
            'transfers' => $this->stockTransferService->list($tenant, $request),
            'branches' => Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'branch_id']),
        ]);
    }

    public function create(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/stock-transfers/Create', [
            'branches' => Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get(['id', 'name']),
            'products' => Product::forTenant($tenant)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }

    public function store(StockTransferRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $transfer = $this->stockTransferService->create($tenant, $request->validated(), $request->user()->id);

        return redirect()
            ->route('tenant.stock-transfers.show', ['tenant' => $tenant->slug, 'transfer' => $transfer->id])
            ->with('success', 'Stock transfer created successfully.');
    }

    public function show(Request $request, string $tenantSlug, int $transfer): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $transfer = $this->stockTransferService->findForTenant($tenant, $transfer);

        return Inertia::render('tenant/stock-transfers/Show', [
            'transfer' => $transfer,
        ]);
    }

    public function ship(Request $request, string $tenantSlug, int $transfer): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $transfer = $this->stockTransferService->findForTenant($tenant, $transfer);

        $sentQuantities = $request->input('sent_quantities', []);
        $this->stockTransferService->ship($transfer, $sentQuantities, $request->user()->id);

        return back()->with('success', 'Stock transfer shipped successfully.');
    }

    public function receive(Request $request, string $tenantSlug, int $transfer): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $transfer = $this->stockTransferService->findForTenant($tenant, $transfer);

        $receivedQuantities = $request->input('received_quantities', []);
        $this->stockTransferService->receive($transfer, $receivedQuantities, $request->user()->id);

        return back()->with('success', 'Stock transfer received successfully.');
    }

    public function cancel(Request $request, string $tenantSlug, int $transfer): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $transfer = $this->stockTransferService->findForTenant($tenant, $transfer);

        $this->stockTransferService->cancel($transfer, $request->user()->id);

        return back()->with('success', 'Stock transfer cancelled.');
    }
}
