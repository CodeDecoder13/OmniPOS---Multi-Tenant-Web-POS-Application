<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Order;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptController extends Controller
{
    public function show(string $token): Response
    {
        $order = Order::where('receipt_token', $token)
            ->with(['items', 'payments', 'customer:id,name'])
            ->firstOrFail();

        $tenant = \App\Models\Tenant::findOrFail($order->tenant_id);

        return Inertia::render('public/Receipt', [
            'order' => [
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'total' => $order->total,
                'status' => $order->status,
                'created_at' => $order->created_at->toISOString(),
                'customer' => $order->customer ? ['name' => $order->customer->name] : null,
                'items' => $order->items->map(fn ($i) => [
                    'product_name' => $i->product_name,
                    'product_price' => $i->product_price,
                    'quantity' => $i->quantity,
                    'subtotal' => $i->subtotal,
                ]),
                'payments' => $order->payments->map(fn ($p) => [
                    'method' => $p->method,
                    'amount' => $p->amount,
                ]),
            ],
            'store' => [
                'name' => $tenant->data['store_name'] ?? $tenant->name,
                'address' => $tenant->data['store_address'] ?? null,
                'phone' => $tenant->data['store_phone'] ?? null,
            ],
        ]);
    }
}
