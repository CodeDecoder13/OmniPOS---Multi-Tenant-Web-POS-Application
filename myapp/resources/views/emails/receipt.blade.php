@component('mail::message')
# Receipt for Order {{ $order->order_number }}

**{{ $tenant->data['store_name'] ?? $tenant->name }}**

@if(!empty($tenant->data['store_address']))
{{ $tenant->data['store_address'] }}
@endif

---

**Date:** {{ $order->created_at->format('M d, Y h:i A') }}
**Order Type:** {{ $order->order_type === 'take_out' ? 'Take Out' : 'Dine In' }}
@if($order->customer)
**Customer:** {{ $order->customer->name }}
@endif

---

### Items

| Item | Qty | Price |
|:-----|:---:|------:|
@foreach($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | ₱{{ number_format($item->subtotal, 2) }} |
@endforeach

---

| | |
|:---|---:|
| **Subtotal** | ₱{{ number_format($order->subtotal, 2) }} |
@if($order->discount_amount > 0)
| Discount | -₱{{ number_format($order->discount_amount, 2) }} |
@endif
@if($order->tax_amount > 0)
| Tax | ₱{{ number_format($order->tax_amount, 2) }} |
@endif
| **Total** | **₱{{ number_format($order->total, 2) }}** |

@component('mail::button', ['url' => $receiptUrl])
View Receipt Online
@endcomponent

Thank you for your purchase!

@endcomponent
