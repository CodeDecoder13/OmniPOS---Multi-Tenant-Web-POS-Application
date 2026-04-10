<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\ShiftClosed;
use App\Mail\ShiftEndedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendShiftEndedEmail implements ShouldQueue
{
    public function handle(ShiftClosed $event): void
    {
        $shift = $event->shift;
        $shift->loadMissing(['tenant', 'operator:id,name', 'branch:id,name']);

        $tenant = $shift->tenant;
        $owner = User::find($tenant->owner_id);

        if (! $owner || ! $owner->email) {
            return;
        }

        // Shift summary stats
        $stats = DB::table('orders')
            ->where('shift_id', $shift->id)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as total_sales,
                COALESCE(COUNT(CASE WHEN status = ? THEN 1 END), 0) as total_orders,
                COALESCE(COUNT(CASE WHEN status = ? THEN 1 END), 0) as voided_count
            ", [OrderStatus::Completed->value, OrderStatus::Completed->value, OrderStatus::Voided->value])
            ->first();

        $totalSales = (float) $stats->total_sales;
        $totalOrders = (int) $stats->total_orders;
        $avgOrderValue = $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0;

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
                'method' => ucfirst($row->method),
                'count' => (int) $row->count,
                'total' => round((float) $row->total, 2),
            ])
            ->values()
            ->toArray();

        // Products sold
        $productsSold = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.shift_id', $shift->id)
            ->where('orders.status', OrderStatus::Completed->value)
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.subtotal) as total_amount')
            )
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_qty')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->product_name,
                'quantity' => (int) $row->total_qty,
                'total' => round((float) $row->total_amount, 2),
            ])
            ->values()
            ->toArray();

        // Hours rendered
        $openedAt = Carbon::parse($shift->opened_at);
        $closedAt = Carbon::parse($shift->closed_at);
        $totalMinutes = (int) round($shift->hours_rendered * 60);
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        $hoursRendered = "{$hours}h {$minutes}m";

        Mail::to($owner->email)->send(new ShiftEndedMail([
            'ownerName' => $owner->name,
            'tenantName' => $tenant->name,
            'operatorName' => $shift->operator->name ?? 'Unknown',
            'branchName' => $shift->branch->name ?? 'Unknown',
            'openedAt' => $openedAt->format('M d, Y h:i A'),
            'closedAt' => $closedAt->format('M d, Y h:i A'),
            'hoursRendered' => $hoursRendered,
            'totalSales' => round($totalSales, 2),
            'totalOrders' => $totalOrders,
            'avgOrderValue' => $avgOrderValue,
            'voidedCount' => (int) $stats->voided_count,
            'startingCash' => round((float) $shift->starting_cash, 2),
            'expectedCash' => round((float) $shift->expected_cash, 2),
            'actualCash' => round((float) $shift->ending_cash, 2),
            'cashDifference' => round((float) $shift->cash_difference, 2),
            'paymentBreakdown' => $paymentBreakdown,
            'productsSold' => $productsSold,
        ]));
    }
}
