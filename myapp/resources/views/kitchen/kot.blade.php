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
        font-size: 12px;
        color: #000;
        width: 226px;
        line-height: 1.4;
    }
    .center {
        text-align: center;
    }
    .bold {
        font-weight: bold;
    }
    .separator {
        border-bottom: 1px dashed #000;
        margin: 6px 0;
    }
    .kot-header {
        font-size: 16px;
        font-weight: bold;
        text-align: center;
        letter-spacing: 2px;
        padding: 4px 0;
    }
    .order-number {
        font-size: 16px;
        font-weight: bold;
        text-align: center;
        padding: 2px 0;
    }
    .item-row {
        display: flex;
        gap: 8px;
        padding: 3px 0;
    }
    .item-qty {
        font-weight: bold;
        font-size: 14px;
        min-width: 28px;
    }
    .item-name {
        font-weight: bold;
        font-size: 13px;
    }
    .item-detail {
        font-size: 10px;
        color: #333;
        padding-left: 36px;
    }
    .notes-label {
        font-weight: bold;
        margin-bottom: 2px;
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
    <div class="kot-header">** KOT **</div>
    <div class="separator"></div>

    <div class="order-number">Order: {{ $order->order_number }}</div>
    <div class="center">{{ $order->created_at->format('M d, Y h:i A') }}</div>

    @if($order->table)
    <div class="center bold" style="font-size: 14px;">Table: {{ $order->table->name }}</div>
    @endif

    @if($order->order_type)
    <div class="center bold">** {{ strtoupper(str_replace('_', ' ', $order->order_type->value)) }} **</div>
    @endif

    <div class="separator"></div>

    @foreach($order->items as $item)
    <div class="item-row">
        <span class="item-qty">{{ $item->quantity }}x</span>
        <span class="item-name">{{ $item->product_name }}</span>
    </div>
    @if($item->variations && $item->variations->count() > 0)
        @foreach($item->variations as $variation)
        <div class="item-detail">- {{ $variation->variation_group_name }}: {{ $variation->option_name }}</div>
        @endforeach
    @endif
    @if($item->itemAddons && $item->itemAddons->count() > 0)
        @foreach($item->itemAddons as $addon)
        <div class="item-detail">+ {{ $addon->addon_name }}</div>
        @endforeach
    @endif
    @endforeach

    @if($order->notes)
    <div class="separator"></div>
    <div class="notes-label">Notes:</div>
    <div>{{ $order->notes }}</div>
    @endif

    @if($order->kitchen_notes)
    <div class="separator"></div>
    <div class="notes-label">Kitchen Notes:</div>
    <div>{{ $order->kitchen_notes }}</div>
    @endif

    <div class="separator"></div>
    <div class="footer">Printed: {{ now()->format('M d, Y h:i A') }}</div>
</body>
</html>
