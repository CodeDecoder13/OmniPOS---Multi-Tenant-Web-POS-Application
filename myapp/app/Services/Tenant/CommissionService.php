<?php

namespace App\Services\Tenant;

use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\Tenant\CommissionPayout;
use App\Models\Tenant\StaffMember;
use App\Support\BusinessTime;
use App\Support\CommissionCalculator;
use App\Support\TenantLock;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /**
     * Sales, commission and tips per staff member for a range of local days.
     *
     * Calculated from the recorded sales every time (nothing is stored), so a refund
     * automatically lowers what is owed. Voided and fully refunded orders do not count.
     * A line with an assistant is shared out by the assistant's percent; each person's share
     * earns at their own rate (or the service's own rate when it has one).
     *
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float|int>}
     */
    public function report(Tenant $tenant, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        [$from, $to] = [BusinessTime::startOfDay($dateFrom), BusinessTime::endOfDay($dateTo)];

        $byStaff = [];
        $add = function (int $staffId, int $services, float $sales, float $commission) use (&$byStaff) {
            $r = $byStaff[$staffId] ?? ['services' => 0, 'sales' => 0.0, 'commission' => 0.0, 'tips' => 0.0];
            $r['services'] += $services;
            $r['sales'] += $sales;
            $r['commission'] += $commission;
            $byStaff[$staffId] = $r;
        };

        // Staff rates, loaded once; the lines only carry ids.
        $rates = StaffMember::forTenant($tenant)->pluck('commission_rate', 'id');

        $refunds = DB::table('refund_items')
            ->select('order_item_id', DB::raw('SUM(amount) as refunded'))
            ->groupBy('order_item_id');

        DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoinSub($refunds, 'rf', 'rf.order_item_id', '=', 'order_items.id')
            ->where('orders.tenant_id', $tenant->id)
            ->where('orders.status', OrderStatus::Completed->value)
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotNull('order_items.staff_member_id')
            ->when($branchId, fn ($q) => $q->where('orders.branch_id', $branchId))
            ->select([
                'order_items.id',
                'order_items.staff_member_id',
                'order_items.assistant_staff_member_id',
                'order_items.assistant_share',
                'order_items.quantity',
                'order_items.subtotal',
                'products.commission_rate as product_rate',
                'orders.subtotal as order_subtotal',
                'orders.discount_amount',
                'orders.promotion_discount',
                DB::raw('COALESCE(rf.refunded, 0) as refunded'),
            ])
            ->orderBy('order_items.id')
            ->chunk(1000, function ($lines) use ($rates, $add) {
                foreach ($lines as $line) {
                    $net = CommissionCalculator::netLine(
                        (float) $line->subtotal,
                        (float) $line->refunded,
                        (float) $line->order_subtotal,
                        (float) $line->discount_amount + (float) $line->promotion_discount,
                    );

                    $share = $line->assistant_staff_member_id ? (float) $line->assistant_share : 0.0;
                    [$mainNet, $assistantNet] = CommissionCalculator::split($net, $share);

                    // A rate set on the service wins over the person's default.
                    $rateFor = fn (int $id): float => $line->product_rate !== null ? (float) $line->product_rate : (float) ($rates[$id] ?? 0);

                    $add((int) $line->staff_member_id, (int) $line->quantity, $mainNet, CommissionCalculator::commission($mainNet, $rateFor((int) $line->staff_member_id)));

                    if ($share > 0) {
                        $add((int) $line->assistant_staff_member_id, (int) $line->quantity, $assistantNet, CommissionCalculator::commission($assistantNet, $rateFor((int) $line->assistant_staff_member_id)));
                    }
                }
            });

        // Tips: to the person named on the sale, otherwise shared equally by everyone on its lines.
        $orders = DB::table('orders')
            ->where('tenant_id', $tenant->id)
            ->where('status', OrderStatus::Completed->value)
            ->where('tip_amount', '>', 0)
            ->whereBetween('created_at', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get(['id', 'tip_amount', 'tip_staff_member_id']);

        foreach ($orders->chunk(500) as $chunk) {
            $people = DB::table('order_items')
                ->whereIn('order_id', $chunk->pluck('id'))
                ->get(['order_id', 'staff_member_id', 'assistant_staff_member_id'])
                ->groupBy('order_id')
                ->map(fn ($items) => $items->flatMap(fn ($i) => [$i->staff_member_id, $i->assistant_staff_member_id])->filter()->unique()->values());

            foreach ($chunk as $order) {
                $tip = (float) $order->tip_amount;
                $recipients = $order->tip_staff_member_id ? collect([$order->tip_staff_member_id]) : ($people[$order->id] ?? collect());

                foreach ($recipients as $staffId) {
                    $byStaff[$staffId] ??= ['services' => 0, 'sales' => 0.0, 'commission' => 0.0, 'tips' => 0.0];
                    $byStaff[$staffId]['tips'] += round($tip / max(1, $recipients->count()), 2);
                }
            }
        }

        $payouts = CommissionPayout::forTenant($tenant)
            ->whereDate('period_from', $dateFrom)
            ->whereDate('period_to', $dateTo)
            ->get()
            ->keyBy('staff_member_id');

        // Everyone active is listed, even with nothing sold, so the report reads as a roster.
        $rows = StaffMember::forTenant($tenant)
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', array_keys($byStaff)))
            ->orderBy('name')
            ->get(['id', 'name', 'commission_rate', 'is_active'])
            ->map(function (StaffMember $s) use ($byStaff, $payouts) {
                $r = $byStaff[$s->id] ?? ['services' => 0, 'sales' => 0.0, 'commission' => 0.0, 'tips' => 0.0];
                $payout = $payouts[$s->id] ?? null;

                return [
                    'staff_id' => $s->id,
                    'name' => $s->name,
                    'is_active' => $s->is_active,
                    'default_rate' => (float) $s->commission_rate,
                    'services' => $r['services'],
                    'sales' => round($r['sales'], 2),
                    'commission' => round($r['commission'], 2),
                    'tips' => round($r['tips'], 2),
                    'due' => round($r['commission'] + $r['tips'], 2),
                    'payout_id' => $payout?->id,
                    'paid_amount' => $payout ? (float) $payout->amount : null,
                    'paid_at' => $payout?->paid_at?->toIso8601String(),
                ];
            })->values()->all();

        return [
            'rows' => $rows,
            'totals' => [
                'services' => array_sum(array_column($rows, 'services')),
                'sales' => round(array_sum(array_column($rows, 'sales')), 2),
                'commission' => round(array_sum(array_column($rows, 'commission')), 2),
                'tips' => round(array_sum(array_column($rows, 'tips')), 2),
                'due' => round(array_sum(array_column($rows, 'due')), 2),
            ],
        ];
    }

    /**
     * Record that a staff member was paid for a period. The amounts are recomputed here from the sales
     * (never taken from the browser) and saved as a snapshot. Periods for one person cannot overlap, so
     * the same sale can never be paid twice.
     */
    public function markPaid(Tenant $tenant, int $staffId, string $dateFrom, string $dateTo, int $userId): CommissionPayout
    {
        return DB::transaction(function () use ($tenant, $staffId, $dateFrom, $dateTo, $userId) {
            $staff = StaffMember::forTenant($tenant)->findOrFail($staffId);

            TenantLock::acquire($tenant->id, "payout-{$staff->id}");

            $overlap = CommissionPayout::forTenant($tenant)
                ->where('staff_member_id', $staff->id)
                ->whereDate('period_from', '<=', $dateTo)
                ->whereDate('period_to', '>=', $dateFrom)
                ->exists();

            abort_if($overlap, 422, "{$staff->name} already has a payout that overlaps these dates. Undo it first if it was a mistake.");

            $row = collect($this->report($tenant, $dateFrom, $dateTo)['rows'])->firstWhere('staff_id', $staff->id);

            abort_if(! $row || $row['due'] <= 0, 422, "Nothing to pay {$staff->name} for these dates.");

            return CommissionPayout::create([
                'tenant_id' => $tenant->id,
                'staff_member_id' => $staff->id,
                'period_from' => $dateFrom,
                'period_to' => $dateTo,
                'sales' => $row['sales'],
                'commission' => $row['commission'],
                'tips' => $row['tips'],
                'amount' => $row['due'],
                'paid_by' => $userId,
                'paid_at' => now(),
            ]);
        });
    }
}
