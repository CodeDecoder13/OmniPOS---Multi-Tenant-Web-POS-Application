<?php

namespace App\Listeners;

use App\Models\Tenant\ActivityLog;

class LogOrderActivity
{
    public function handle(object $event): void
    {
        $order = $event->order;
        $tenant = property_exists($event, 'tenant') ? $event->tenant : null;

        $action = match (true) {
            $event instanceof \App\Events\OrderCompleted => 'order.completed',
            $event instanceof \App\Events\OrderVoided => 'order.voided',
            $event instanceof \App\Events\OrderRefunded => 'order.refunded',
            default => 'order.unknown',
        };

        $properties = ['order_number' => $order->order_number, 'total' => $order->total];

        if ($event instanceof \App\Events\OrderVoided && $event->voidReason) {
            $properties['void_reason'] = $event->voidReason;
        }

        if ($event instanceof \App\Events\OrderRefunded) {
            $properties['refund_number'] = $event->refund->refund_number;
            $properties['refund_amount'] = $event->refund->amount;
        }

        ActivityLog::create([
            'tenant_id' => $tenant?->id ?? $order->tenant_id,
            'user_id' => $order->created_by ?? 0,
            'action' => $action,
            'subject_type' => 'order',
            'subject_id' => $order->id,
            'properties' => $properties,
        ]);
    }
}
