<?php

namespace App\Services\Tenant;

use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\Tenant\StaffMember;
use App\Support\BusinessTime;
use App\Support\CommissionCalculator;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /**
     * Sales and commission per staff member for a range of local days.
     *
     * Calculated from the recorded sales every time (nothing is stored), so a refund
     * automatically lowers what is owed. Voided and fully refunded orders do not count.
     *
     * @return array{rows: list<array<string, mixed>>, totals: array{services: int, sales: float, commission: float}}
     */
    public function report(Tenant $tenant, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $refunds = DB::table('refund_items')
            ->select('order_item_id', DB::raw('SUM(amount) as refunded'))
            ->groupBy('order_item_id');

        $lines = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('staff_members', 'staff_members.id', '=', 'order_items.staff_member_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoinSub($refunds, 'rf', 'rf.order_item_id', '=', 'order_items.id')
            ->where('orders.tenant_id', $tenant->id)
            ->where('staff_members.tenant_id', $tenant->id)
            ->where('orders.status', OrderStatus::Completed->value)
            ->whereBetween('orders.created_at', [BusinessTime::startOfDay($dateFrom), BusinessTime::endOfDay($dateTo)])
            ->when($branchId, fn ($q) => $q->where('orders.branch_id', $branchId))
            ->select([
                'staff_members.id as staff_id',
                'staff_members.commission_rate as staff_rate',
                'products.commission_rate as product_rate',
                'order_items.quantity',
                'order_items.subtotal',
                'orders.subtotal as order_subtotal',
                'orders.discount_amount',
                'orders.promotion_discount',
                DB::raw('COALESCE(rf.refunded, 0) as refunded'),
            ])
            ->get();

        $byStaff = [];
        foreach ($lines as $line) {
            $net = CommissionCalculator::netLine(
                (float) $line->subtotal,
                (float) $line->refunded,
                (float) $line->order_subtotal,
                (float) $line->discount_amount + (float) $line->promotion_discount,
            );

            // A rate set on the service wins over the person's default.
            $rate = $line->product_rate !== null ? (float) $line->product_rate : (float) $line->staff_rate;

            $row = $byStaff[$line->staff_id] ??= ['services' => 0, 'sales' => 0.0, 'commission' => 0.0];
            $row['services'] += (int) $line->quantity;
            $row['sales'] += $net;
            $row['commission'] += CommissionCalculator::commission($net, $rate);
            $byStaff[$line->staff_id] = $row;
        }

        // Everyone active is listed, even with nothing sold, so the report reads as a roster.
        $rows = StaffMember::forTenant($tenant)
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', array_keys($byStaff)))
            ->orderBy('name')
            ->get(['id', 'name', 'commission_rate', 'is_active'])
            ->map(function (StaffMember $s) use ($byStaff) {
                $r = $byStaff[$s->id] ?? ['services' => 0, 'sales' => 0.0, 'commission' => 0.0];

                return [
                    'staff_id' => $s->id,
                    'name' => $s->name,
                    'is_active' => $s->is_active,
                    'default_rate' => (float) $s->commission_rate,
                    'services' => $r['services'],
                    'sales' => round($r['sales'], 2),
                    'commission' => round($r['commission'], 2),
                ];
            })->values()->all();

        return [
            'rows' => $rows,
            'totals' => [
                'services' => array_sum(array_column($rows, 'services')),
                'sales' => round(array_sum(array_column($rows, 'sales')), 2),
                'commission' => round(array_sum(array_column($rows, 'commission')), 2),
            ],
        ];
    }
}
