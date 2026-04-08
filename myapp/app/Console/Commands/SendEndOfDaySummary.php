<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Models\User;
use App\Notifications\EndOfDaySummaryNotification;
use Illuminate\Console\Command;

class SendEndOfDaySummary extends Command
{
    protected $signature = 'reports:daily-summary';

    protected $description = 'Send end-of-day summary notification to tenant owners';

    public function handle(): int
    {
        $tenants = Tenant::where('is_active', true)->get();
        $today = now()->toDateString();
        $sentCount = 0;

        foreach ($tenants as $tenant) {
            $orders = Order::where('tenant_id', $tenant->id)
                ->whereDate('created_at', $today)
                ->get();

            $completedOrders = $orders->where('status', OrderStatus::Completed);

            if ($completedOrders->isEmpty()) {
                continue;
            }

            $stats = [
                'date' => $today,
                'tenant_name' => $tenant->name,
                'order_count' => $completedOrders->count(),
                'total_revenue' => round($completedOrders->sum('total'), 2),
                'avg_order_value' => round($completedOrders->avg('total'), 2),
                'voided_count' => $orders->where('status', OrderStatus::Voided)->count(),
                'refunded_count' => $orders->where('status', OrderStatus::Refunded)->count(),
            ];

            $owner = User::find($tenant->owner_id);
            if ($owner) {
                $owner->notify(new EndOfDaySummaryNotification($stats));
                $sentCount++;
            }
        }

        $this->info("Sent {$sentCount} daily summary notification(s).");

        return self::SUCCESS;
    }
}
