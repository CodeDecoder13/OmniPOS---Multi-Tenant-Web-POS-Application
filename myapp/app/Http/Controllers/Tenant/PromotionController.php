<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PromotionRequest;
use App\Services\Tenant\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function __construct(
        private readonly PromotionService $promotionService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');

        return Inertia::render('tenant/promotions/Index', [
            'promotions' => $this->promotionService->list($tenant, $request),
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    public function create(Request $request, string $tenantSlug): Response
    {
        return Inertia::render('tenant/promotions/Create');
    }

    public function store(PromotionRequest $request, string $tenantSlug): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $this->promotionService->create($tenant, $request->validated());

        return redirect()
            ->route('tenant.promotions.index', ['tenant' => $tenant->slug])
            ->with('success', 'Promotion created successfully.');
    }

    public function edit(Request $request, string $tenantSlug, int $promotion): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $promotion = $this->promotionService->findForTenant($tenant, $promotion);

        return Inertia::render('tenant/promotions/Edit', [
            'promotion' => $promotion,
        ]);
    }

    public function update(PromotionRequest $request, string $tenantSlug, int $promotion): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $promotion = $this->promotionService->findForTenant($tenant, $promotion);

        $this->promotionService->update($promotion, $request->validated());

        return redirect()
            ->route('tenant.promotions.index', ['tenant' => $tenant->slug])
            ->with('success', 'Promotion updated successfully.');
    }

    public function destroy(Request $request, string $tenantSlug, int $promotion): RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $promotion = $this->promotionService->findForTenant($tenant, $promotion);

        try {
            $this->promotionService->delete($promotion);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.promotions.index', ['tenant' => $tenant->slug])
            ->with('success', 'Promotion deleted successfully.');
    }

    public function usage(Request $request, string $tenantSlug, int $promotion): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $promotion = $this->promotionService->findForTenant($tenant, $promotion);

        $orders = $promotion->orders()
            ->with('customer:id,name')
            ->select('id', 'promotion_id', 'order_number', 'customer_id', 'discount_customer_name', 'promotion_discount', 'total', 'created_at')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer?->name ?? $order->discount_customer_name ?? 'Walk-in',
                'promotion_discount' => $order->promotion_discount,
                'total' => $order->total,
                'created_at' => $order->created_at->toDateTimeString(),
            ]);

        return response()->json($orders);
    }

    public function applyCode(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $result = $this->promotionService->applyPromoCode(
            $tenant,
            $request->input('code'),
            (float) $request->input('subtotal'),
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
