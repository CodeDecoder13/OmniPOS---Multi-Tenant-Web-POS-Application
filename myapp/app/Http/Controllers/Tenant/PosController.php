<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CheckoutRequest;
use App\Http\Requests\Tenant\HoldOrderRequest;
use App\Models\Tenant\Category;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Order;
use App\Models\TenantUser;
use App\Services\Tenant\CustomerService;
use App\Services\Tenant\HeldOrderService;
use App\Services\Tenant\PosService;
use App\Services\Tenant\PromotionService;
use App\Services\Tenant\ShiftService;
use App\Services\Tenant\TableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function __construct(
        private readonly PosService $posService,
        private readonly CustomerService $customerService,
        private readonly PromotionService $promotionService,
    ) {}

    public function index(Request $request, string $tenantSlug): Response
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $branchId = $tenantUser->branch_id ?? null;

        $branch = $branchId ? Branch::find($branchId) : null;

        return Inertia::render('tenant/pos/Index', [
            'categories' => Category::forTenant($tenant)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'tables' => app(TableService::class)->getAvailableForBranch($tenant, $branchId),
            'presetDiscounts' => $this->promotionService->getPresetDiscounts($tenant),
            'branchSettings' => $branch?->getSettings(),
        ]);
    }

    public function products(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $branchId = $tenantUser->branch_id ?? null;

        return response()->json(
            $this->posService->getProducts($tenant, $request, $branchId)
        );
    }

    public function searchCustomers(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        return response()->json(
            $this->customerService->searchForPos($tenant, $request->input('search'))
        );
    }

    public function storeCustomer(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[+]?[\d\s\-().]+$/'],
        ]);

        $customer = $this->customerService->create($tenant, $validated, $request->user()->id);

        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
        ], 201);
    }

    public function checkout(CheckoutRequest $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');

        $operatorId = $request->validated('pos_operator_id');
        if ($operatorId && (int) $operatorId !== (int) $request->user()->id) {
            // Operator must exist in this tenant
            $exists = TenantUser::where('tenant_id', $tenant->id)
                ->where('user_id', $operatorId)->exists();
            if (! $exists) {
                return response()->json(['message' => 'Invalid POS operator.'], 422);
            }

            // Operator must have been verified via PIN (session proof)
            $verifiedId = $request->session()->get('pos_verified_operator_id');
            $verifiedAt = $request->session()->get('pos_verified_operator_at');
            $maxAge = 8 * 60 * 60; // 8 hours

            if ((int) $verifiedId !== (int) $operatorId || ! $verifiedAt || (now()->timestamp - $verifiedAt) > $maxAge) {
                return response()->json(['message' => 'POS operator must verify their PIN first.'], 403);
            }
        } else {
            $operatorId = $request->user()->id;
        }

        $activeShift = app(ShiftService::class)->getOpenShift($tenant, $operatorId);

        if (! $activeShift) {
            return response()->json(['message' => 'No active shift. Please open a shift before processing orders.'], 422);
        }

        $checkoutData = $request->validated();
        $checkoutData['shift_id'] = $activeShift->id;

        $order = $this->posService->checkout(
            $tenant,
            $checkoutData,
            $operatorId,
            $tenantUser->branch_id ?? null,
        );

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    public function holdOrder(HoldOrderRequest $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $heldOrderService = app(HeldOrderService::class);

        $order = $heldOrderService->hold(
            $tenant,
            $request->validated(),
            $request->user()->id,
            $tenantUser->branch_id ?? null,
        );

        return response()->json(['success' => true, 'order' => $order]);
    }

    public function heldOrders(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $heldOrderService = app(HeldOrderService::class);

        return response()->json(
            $heldOrderService->listHeld($tenant, $tenantUser->branch_id ?? null)
        );
    }

    public function recallOrder(Request $request, string $tenantSlug, int $order): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $orderModel = Order::forTenant($tenant)->where('status', 'pending')->whereNotNull('held_at')->findOrFail($order);
        $heldOrderService = app(HeldOrderService::class);

        return response()->json($heldOrderService->recall($orderModel));
    }

    public function deleteHeldOrder(Request $request, string $tenantSlug, int $order): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $orderModel = Order::forTenant($tenant)->where('status', 'pending')->whereNotNull('held_at')->findOrFail($order);
        $heldOrderService = app(HeldOrderService::class);

        $heldOrderService->delete($orderModel);

        return response()->json(['success' => true]);
    }

    public function billingHistory(Request $request, string $tenantSlug): JsonResponse
    {
        $tenant = $request->attributes->get('current_tenant');
        $tenantUser = $request->attributes->get('current_tenant_user');
        $branchId = $tenantUser->branch_id ?? null;

        $query = Order::forTenant($tenant)
            ->where('status', OrderStatus::Completed)
            ->whereNull('held_at')
            ->whereDate('created_at', now()->toDateString())
            ->with('customer:id,name')
            ->withCount('items')
            ->select(['id', 'order_number', 'customer_id', 'total', 'created_at'])
            ->orderByDesc('created_at')
            ->limit(10);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return response()->json($query->get()->map(fn ($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer?->name ?? 'Walk-in',
            'items_count' => $order->items_count,
            'total' => $order->total,
            'time' => $order->created_at->format('g:i A'),
        ]));
    }
}
