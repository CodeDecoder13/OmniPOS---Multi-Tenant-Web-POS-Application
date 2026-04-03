<?php

namespace App\Services\Central;

use App\Models\Tenant\ActivityLog;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\Shift;
use App\Models\UserLogin;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TenantActivityService
{
    public function getSummaryStats(string $tenantId): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::now()->subHours(24);

        return [
            'logins_today' => UserLogin::where('tenant_id', $tenantId)
                ->where('logged_in_at', '>=', $today)
                ->count(),

            'active_users_24h' => UserLogin::where('tenant_id', $tenantId)
                ->where('logged_in_at', '>=', $yesterday)
                ->distinct('user_id')
                ->count('user_id'),

            'orders_today' => Order::where('tenant_id', $tenantId)
                ->where('created_at', '>=', $today)
                ->count(),

            'open_shifts' => Shift::where('tenant_id', $tenantId)
                ->where('status', 'open')
                ->count(),
        ];
    }

    public function getTimeline(string $tenantId, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $dateFrom = ! empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $dateTo = ! empty($filters['date_to'])
            ? Carbon::parse($filters['date_to'])->endOfDay()
            : Carbon::now()->endOfDay();

        $userId = $filters['user_id'] ?? null;
        $eventType = $filters['event_type'] ?? null;

        $events = collect();

        // 1. User Logins
        if (! $eventType || $eventType === 'login') {
            $logins = UserLogin::where('user_logins.tenant_id', $tenantId)
                ->whereBetween('user_logins.logged_in_at', [$dateFrom, $dateTo])
                ->when($userId, fn ($q) => $q->where('user_logins.user_id', $userId))
                ->join('users', 'users.id', '=', 'user_logins.user_id')
                ->select('user_logins.id', 'user_logins.user_id', 'users.name as user_name', 'user_logins.ip_address', 'user_logins.logged_in_at')
                ->get()
                ->map(fn ($login) => [
                    'event_type' => 'login',
                    'source_id' => $login->id,
                    'user_id' => $login->user_id,
                    'user_name' => $login->user_name,
                    'description' => "Logged in from {$login->ip_address}",
                    'occurred_at' => $login->logged_in_at,
                    'properties' => null,
                ]);
            $events = $events->concat($logins);
        }

        // 2. Activity Logs
        if (! $eventType || $eventType === 'activity') {
            $activities = ActivityLog::where('activity_logs.tenant_id', $tenantId)
                ->whereBetween('activity_logs.created_at', [$dateFrom, $dateTo])
                ->when($userId, fn ($q) => $q->where('activity_logs.user_id', $userId))
                ->join('users', 'users.id', '=', 'activity_logs.user_id')
                ->select('activity_logs.id', 'activity_logs.user_id', 'users.name as user_name', 'activity_logs.action', 'activity_logs.properties', 'activity_logs.created_at')
                ->get()
                ->map(fn ($log) => [
                    'event_type' => 'activity',
                    'source_id' => $log->id,
                    'user_id' => $log->user_id,
                    'user_name' => $log->user_name,
                    'description' => $log->action,
                    'occurred_at' => $log->created_at,
                    'properties' => $log->properties,
                ]);
            $events = $events->concat($activities);
        }

        // 3. Shifts opened
        if (! $eventType || $eventType === 'shift_open') {
            $shiftsOpened = Shift::where('shifts.tenant_id', $tenantId)
                ->whereBetween('shifts.opened_at', [$dateFrom, $dateTo])
                ->when($userId, fn ($q) => $q->where('shifts.user_id', $userId))
                ->join('users', 'users.id', '=', 'shifts.user_id')
                ->leftJoin('branches', 'branches.id', '=', 'shifts.branch_id')
                ->select('shifts.id', 'shifts.user_id', 'users.name as user_name', 'branches.name as branch_name', 'shifts.opened_at')
                ->get()
                ->map(fn ($shift) => [
                    'event_type' => 'shift_open',
                    'source_id' => $shift->id,
                    'user_id' => $shift->user_id,
                    'user_name' => $shift->user_name,
                    'description' => 'Opened shift at ' . ($shift->branch_name ?? 'Unknown branch'),
                    'occurred_at' => $shift->opened_at,
                    'properties' => null,
                ]);
            $events = $events->concat($shiftsOpened);
        }

        // 4. Shifts closed
        if (! $eventType || $eventType === 'shift_close') {
            $shiftsClosed = Shift::where('shifts.tenant_id', $tenantId)
                ->whereNotNull('shifts.closed_at')
                ->whereBetween('shifts.closed_at', [$dateFrom, $dateTo])
                ->when($userId, fn ($q) => $q->where('shifts.user_id', $userId))
                ->join('users', 'users.id', '=', 'shifts.user_id')
                ->select('shifts.id', 'shifts.user_id', 'users.name as user_name', 'shifts.total_sales', 'shifts.closed_at')
                ->get()
                ->map(fn ($shift) => [
                    'event_type' => 'shift_close',
                    'source_id' => $shift->id,
                    'user_id' => $shift->user_id,
                    'user_name' => $shift->user_name,
                    'description' => 'Closed shift (sales: ₱' . number_format((float) $shift->total_sales, 2) . ')',
                    'occurred_at' => $shift->closed_at,
                    'properties' => null,
                ]);
            $events = $events->concat($shiftsClosed);
        }

        // 5. Orders
        if (! $eventType || $eventType === 'order') {
            $orders = Order::where('orders.tenant_id', $tenantId)
                ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
                ->when($userId, fn ($q) => $q->where('orders.created_by', $userId))
                ->join('users', 'users.id', '=', 'orders.created_by')
                ->select('orders.id', 'orders.created_by', 'users.name as user_name', 'orders.order_number', 'orders.total', 'orders.created_at')
                ->get()
                ->map(fn ($order) => [
                    'event_type' => 'order',
                    'source_id' => $order->id,
                    'user_id' => $order->created_by,
                    'user_name' => $order->user_name,
                    'description' => "Created order #{$order->order_number} (₱" . number_format((float) $order->total, 2) . ')',
                    'occurred_at' => $order->created_at,
                    'properties' => null,
                ]);
            $events = $events->concat($orders);
        }

        // 6. Products created
        if (! $eventType || $eventType === 'product_created') {
            $products = Product::where('products.tenant_id', $tenantId)
                ->whereBetween('products.created_at', [$dateFrom, $dateTo])
                ->when($userId, fn ($q) => $q->where('products.created_by', $userId))
                ->join('users', 'users.id', '=', 'products.created_by')
                ->select('products.id', 'products.created_by', 'users.name as user_name', 'products.name', 'products.created_at')
                ->get()
                ->map(fn ($product) => [
                    'event_type' => 'product_created',
                    'source_id' => $product->id,
                    'user_id' => $product->created_by,
                    'user_name' => $product->user_name,
                    'description' => "Created product: {$product->name}",
                    'occurred_at' => $product->created_at,
                    'properties' => null,
                ]);
            $events = $events->concat($products);
        }

        // Sort by occurred_at desc
        $sorted = $events->sortByDesc('occurred_at')->values();

        // Manual pagination
        $page = request()->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $items = $sorted->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $sorted->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function getTenantUsers(string $tenantId): Collection
    {
        return \App\Models\User::whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
