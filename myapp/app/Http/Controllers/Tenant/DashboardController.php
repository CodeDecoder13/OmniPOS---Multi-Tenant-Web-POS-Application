<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Payment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, string $tenantSlug): Response|RedirectResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        if ($tenant->branches()->count() === 0) {
            return redirect()->route('tenant.setup', ['tenant' => $tenant->slug]);
        }

        $branchesCount = $tenant->branches()->count();
        $activeBranchesCount = $tenant->branches()->where('is_active', true)->count();
        $usersCount = $tenant->users()->count();
        $rolesCount = Role::forTenant($tenant->id)->count();
        $categoriesCount = $tenant->categories()->count();
        $productsCount = $tenant->products()->count();

        $plan = $tenant->subscription?->plan;

        // Today's metrics
        $today = Carbon::today();
        $todayOrders = Order::forTenant($tenant)
            ->where('status', OrderStatus::Completed)
            ->whereDate('created_at', $today);

        $todayRevenue = (clone $todayOrders)->sum('total');
        $todayOrderCount = (clone $todayOrders)->count();

        // Yesterday for comparison
        $yesterday = Carbon::yesterday();
        $yesterdayRevenue = Order::forTenant($tenant)
            ->where('status', OrderStatus::Completed)
            ->whereDate('created_at', $yesterday)
            ->sum('total');

        // Last 7 days sales trend (single query)
        $sevenDaysAgo = Carbon::today()->subDays(6);
        $trendData = Order::forTenant($tenant)
            ->where('status', OrderStatus::Completed)
            ->whereDate('created_at', '>=', $sevenDaysAgo)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('date');

        $salesTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $row = $trendData->get($dateKey);

            $salesTrend[] = [
                'date' => $date->format('M d'),
                'day' => $date->format('D'),
                'revenue' => round((float) ($row->revenue ?? 0), 2),
                'orders' => (int) ($row->orders ?? 0),
            ];
        }

        // Order status breakdown (last 30 days)
        $ordersByStatus = Order::forTenant($tenant)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Payment method breakdown (last 30 days)
        $paymentsByMethod = Payment::whereHas('order', function ($q) use ($tenant) {
            $q->where('tenant_id', $tenant->id)
                ->where('status', OrderStatus::Completed);
        })
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select('method', DB::raw('sum(amount) as total'))
            ->groupBy('method')
            ->pluck('total', 'method')
            ->map(fn ($v) => round((float) $v, 2))
            ->toArray();

        // Top 5 products (last 30 days)
        $topProducts = OrderItem::whereHas('order', function ($q) use ($tenant) {
            $q->where('tenant_id', $tenant->id)
                ->where('status', OrderStatus::Completed)
                ->where('created_at', '>=', Carbon::now()->subDays(30));
        })
            ->select('product_name', DB::raw('sum(quantity) as qty'), DB::raw('sum(subtotal) as revenue'))
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->product_name,
                'qty' => (int) $item->qty,
                'revenue' => round((float) $item->revenue, 2),
            ])
            ->toArray();

        // Recent orders (last 10)
        $recentOrders = Order::forTenant($tenant)
            ->with('branch:id,name')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total' => round((float) $order->total, 2),
                'status' => $order->status,
                'branch' => $order->branch?->name ?? '-',
                'created_at' => $order->created_at->diffForHumans(),
            ])
            ->toArray();

        return Inertia::render('tenant/Dashboard', [
            'stats' => [
                'branches_count' => $branchesCount,
                'active_branches_count' => $activeBranchesCount,
                'users_count' => $usersCount,
                'roles_count' => $rolesCount,
                'categories_count' => $categoriesCount,
                'products_count' => $productsCount,
                'plan_name' => $plan?->name ?? 'No Plan',
                'subscription_status' => $tenant->subscription?->status ?? 'none',
                'max_branches' => $plan?->max_branches,
                'max_users' => $plan?->max_users,
                'max_products' => $plan?->max_products,
            ],
            'todayRevenue' => round((float) $todayRevenue, 2),
            'todayOrderCount' => $todayOrderCount,
            'yesterdayRevenue' => round((float) $yesterdayRevenue, 2),
            'salesTrend' => $salesTrend,
            'ordersByStatus' => $ordersByStatus,
            'paymentsByMethod' => $paymentsByMethod,
            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders,
        ]);
    }
}
