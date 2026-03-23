<?php

namespace App\Services\Tenant;

use App\Enums\OrderStatus;
use App\Enums\ShiftStatus;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Order;
use App\Models\Tenant\Shift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    public function openShift(Tenant $tenant, int $userId, array $data): Shift
    {
        $existing = $this->getOpenShift($tenant, $userId);

        if ($existing) {
            abort(422, 'You already have an open shift.');
        }

        // Validate branch belongs to tenant
        if (! empty($data['branch_id'])) {
            $branch = Branch::forTenant($tenant)->where('id', $data['branch_id'])->first();
            if (! $branch) {
                throw ValidationException::withMessages([
                    'branch_id' => 'The selected branch does not belong to this tenant.',
                ]);
            }
        }

        return Shift::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $data['branch_id'] ?? null,
            'user_id' => $userId,
            'starting_cash' => $data['starting_cash'],
            'status' => ShiftStatus::Open,
            'opened_at' => now(),
        ]);
    }

    public function closeShift(Shift $shift, array $data): Shift
    {
        if (! $shift->isOpen()) {
            abort(422, 'This shift is already closed.');
        }

        return DB::transaction(function () use ($shift, $data) {
            $orders = Order::where('shift_id', $shift->id)
                ->where('status', OrderStatus::Completed)
                ->get();

            $totalSales = $orders->sum('total');
            $totalOrders = $orders->count();

            // Sum cash payments for expected cash calculation
            $cashPayments = DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->where('orders.shift_id', $shift->id)
                ->where('orders.status', OrderStatus::Completed->value)
                ->where('payments.method', 'cash')
                ->where('payments.status', 'completed')
                ->sum('payments.amount');

            $expectedCash = bcadd((string) $shift->starting_cash, (string) $cashPayments, 2);
            $endingCash = (string) $data['ending_cash'];
            $cashDifference = bcsub($endingCash, $expectedCash, 2);

            $shift->update([
                'ending_cash' => $endingCash,
                'expected_cash' => $expectedCash,
                'cash_difference' => $cashDifference,
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'notes' => $data['notes'] ?? $shift->notes,
                'status' => ShiftStatus::Closed,
                'closed_at' => now(),
            ]);

            return $shift->fresh();
        });
    }

    public function getOpenShift(Tenant $tenant, int $userId): ?Shift
    {
        return Shift::forTenant($tenant)
            ->where('user_id', $userId)
            ->where('status', ShiftStatus::Open)
            ->first();
    }

    public function list(Tenant $tenant, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Shift::forTenant($tenant)
            ->with(['operator:id,name', 'branch:id,name']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('opened_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('opened_at', '<=', $dateTo);
        }

        return $query->latest('opened_at')->paginate($perPage)->withQueryString();
    }

    public function findForTenant(Tenant $tenant, int $shiftId): Shift
    {
        return Shift::forTenant($tenant)
            ->with(['operator:id,name', 'branch:id,name'])
            ->findOrFail($shiftId);
    }

    public function getShiftSummary(Shift $shift): array
    {
        // Use aggregate query instead of loading all orders
        $stats = Order::where('shift_id', $shift->id)
            ->selectRaw("
                SUM(CASE WHEN status = ? THEN total ELSE 0 END) as total_sales,
                COUNT(CASE WHEN status = ? THEN 1 END) as total_orders,
                COUNT(CASE WHEN status = ? THEN 1 END) as voided_count
            ", [OrderStatus::Completed->value, OrderStatus::Completed->value, OrderStatus::Voided->value])
            ->first();

        $totalSales = (float) ($stats->total_sales ?? 0);
        $totalOrders = (int) ($stats->total_orders ?? 0);
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
        $voidedCount = (int) ($stats->voided_count ?? 0);

        // Payment breakdown
        $paymentBreakdown = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.shift_id', $shift->id)
            ->where('orders.status', OrderStatus::Completed->value)
            ->where('payments.status', 'completed')
            ->select('payments.method', DB::raw('COUNT(*) as count'), DB::raw('SUM(payments.amount) as total'))
            ->groupBy('payments.method')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method,
                'count' => (int) $row->count,
                'total' => round((float) $row->total, 2),
            ])
            ->values()
            ->toArray();

        return [
            'total_sales' => round($totalSales, 2),
            'total_orders' => $totalOrders,
            'avg_order_value' => round($avgOrderValue, 2),
            'voided_count' => $voidedCount,
            'payment_breakdown' => $paymentBreakdown,
        ];
    }
}
