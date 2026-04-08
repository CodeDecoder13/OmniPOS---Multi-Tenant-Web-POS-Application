<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        color: #000;
        width: 226px;
        line-height: 1.4;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    td {
        vertical-align: top;
        padding: 1px 0;
    }
    .center {
        text-align: center;
    }
    .right {
        text-align: right;
    }
    .bold {
        font-weight: bold;
    }
    .separator {
        border-bottom: 1px dashed #000;
        margin: 4px 0;
    }
    .store-name {
        font-size: 13px;
        font-weight: bold;
        text-align: center;
        padding: 4px 0;
    }
    .total-row td {
        font-weight: bold;
        font-size: 13px;
        padding-top: 4px;
        border-top: 1px dashed #000;
    }
    .item-detail {
        font-size: 10px;
        color: #333;
    }
    .footer {
        text-align: center;
        font-size: 10px;
        color: #666;
        padding-top: 4px;
    }
</style>
</head>
<body>
    <div class="store-name">{{ $tenant->data['store_name'] ?? $tenant->name }}</div>
    @if(!empty($tenant->data['store_address']))
    <div class="center" style="font-size: 10px; color: #333;">{{ $tenant->data['store_address'] }}</div>
    @endif
    @if(!empty($tenant->data['store_phone']))
    <div class="center" style="font-size: 10px; color: #333;">{{ $tenant->data['store_phone'] }}</div>
    @endif
    @if(!empty($tenant->data['receipt_header']))
    <div class="center" style="font-size: 10px; color: #333; padding-top: 2px; white-space: pre-line;">{{ $tenant->data['receipt_header'] }}</div>
    @endif

    <div class="separator"></div>

    <table>
        <tr>
            <td>Order:</td>
            <td class="right">{{ $order->order_number }}</td>
        </tr>
        <tr>
            <td>Date:</td>
            <td class="right">{{ $order->created_at->format('M d, Y h:i A') }}</td>
        </tr>
        @if($order->creator)
        <tr>
            <td>Cashier:</td>
            <td class="right">{{ $order->creator->name }}</td>
        </tr>
        @endif
        @if($order->customer)
        <tr>
            <td>Customer:</td>
            <td class="right">{{ $order->customer->name }}</td>
        </tr>
        @endif
    </table>

    <div class="separator"></div>

    <table>
        @foreach($order->items as $item)
        <tr>
            <td>{{ $item->product_name }}</td>
            <td class="right">₱{{ number_format($item->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="item-detail">
                {{ $item->quantity }} x ₱{{ number_format($item->product_price, 2) }}
            </td>
        </tr>
        @if($item->notes)
        <tr>
            <td colspan="2" class="item-detail" style="font-style: italic;">
                Note: {{ $item->notes }}
            </td>
        </tr>
        @endif
        @endforeach
    </table>

    <div class="separator"></div>

    <table>
        <tr>
            <td>Subtotal</td>
            <td class="right">₱{{ number_format($order->subtotal, 2) }}</td>
        </tr>
        @if($order->discount_amount > 0)
        <tr>
            <td>Discount</td>
            <td class="right">-₱{{ number_format($order->discount_amount, 2) }}</td>
        </tr>
        @endif
        @if($order->tax_amount > 0)
        <tr>
            <td>{{ $tenant->data['tax_label'] ?? 'Tax' }}</td>
            <td class="right">₱{{ number_format($order->tax_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td>TOTAL</td>
            <td class="right">₱{{ number_format($order->total, 2) }}</td>
        </tr>
    </table>

    @if($order->payments->count() > 0)
    <div class="separator"></div>

    <table>
        @foreach($order->payments as $p)
        @if(!$p->refund_id)
        <tr>
            <td>Payment{{ $order->payments->where('refund_id', null)->count() > 1 ? ' ' . $loop->iteration : '' }}</td>
            <td class="right">{{ $p->method->label() }} — ₱{{ number_format($p->amount, 2) }}</td>
        </tr>
        @if($p->amount_tendered && $p->method->value === 'cash')
        <tr>
            <td>Tendered</td>
            <td class="right">₱{{ number_format($p->amount_tendered, 2) }}</td>
        </tr>
        @endif
        @if($p->change_amount > 0)
        <tr>
            <td>Change</td>
            <td class="right">₱{{ number_format($p->change_amount, 2) }}</td>
        </tr>
        @endif
        @if($p->reference_number && !str_starts_with($p->reference_number, 'REFUND:'))
        <tr>
            <td>Ref#</td>
            <td class="right">{{ $p->reference_number }}</td>
        </tr>
        @endif
        @endif
        @endforeach
    </table>
    @endif

    <div class="separator"></div>

    <div class="footer">
        @if(!empty($tenant->data['receipt_footer']))
            <span style="white-space: pre-line;">{{ $tenant->data['receipt_footer'] }}</span>
        @else
            Thank you for your purchase!
        @endif
    </div>
</body>
</html>
