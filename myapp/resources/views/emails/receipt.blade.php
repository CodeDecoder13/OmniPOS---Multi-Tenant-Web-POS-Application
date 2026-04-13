<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body style="margin: 0; padding: 0; background-color: #f0fdfa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f0fdfa;">
<tr>
<td align="center" style="padding: 40px 0;">
<table width="570" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 570px; width: 100%;">

    {{-- Header --}}
    <tr>
    <td style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 50%, #2dd4bf 100%); padding: 30px 0; text-align: center; border-radius: 8px 8px 0 0;">
        <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;" role="presentation">
        <tr>
            <td style="vertical-align: middle; padding-right: 12px;">
                <img src="{{ config('app.url') }}/images/icon.png" alt="OmniPOS" width="40" height="40" style="display: block;" />
            </td>
            <td style="vertical-align: middle;">
                <span style="color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">OmniPOS</span>
            </td>
        </tr>
        </table>
    </td>
    </tr>

    {{-- Body --}}
    <tr>
    <td style="background-color: #ffffff; padding: 32px 40px; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;">

        <h2 style="margin: 0 0 20px; color: #0f172a; font-size: 20px; font-weight: 600;">Your Receipt</h2>

        @php
            $storeName = $tenant->data['store_name'] ?? $tenant->name;
            $customerName = $order->customer->name ?? 'Valued Customer';
        @endphp

        <p style="margin: 0 0 8px; color: #334155; font-size: 15px; line-height: 1.6;">Hi {{ $customerName }},</p>
        <p style="margin: 0 0 24px; color: #334155; font-size: 15px; line-height: 1.6;">
            Thank you for your purchase at <strong>{{ $storeName }}</strong>. Here's your receipt.
        </p>

        {{-- Order Details --}}
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f0fdfa; border-radius: 8px; border: 1px solid #ccfbf1; margin-bottom: 24px;">
        <tr>
            <td style="padding: 20px;">
                <span style="color: #0d9488; font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px;">Order Details</span>
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td style="padding-bottom: 6px; color: #64748b; font-size: 13px;">Order Number:</td>
                    <td style="padding-bottom: 6px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $order->order_number }}</td>
                </tr>
                <tr>
                    <td style="padding-bottom: 6px; color: #64748b; font-size: 13px;">Date & Time:</td>
                    <td style="padding-bottom: 6px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $order->created_at->format('M d, Y h:i A') }}</td>
                </tr>
                <tr>
                    <td style="padding-bottom: 6px; color: #64748b; font-size: 13px;">Order Type:</td>
                    <td style="padding-bottom: 6px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $order->order_type->label() }}</td>
                </tr>
                @if($order->table)
                <tr>
                    <td style="color: #64748b; font-size: 13px;">Table:</td>
                    <td style="color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $order->table->name }}</td>
                </tr>
                @endif
                </table>
            </td>
        </tr>
        </table>

        {{-- Items --}}
        <span style="color: #0f172a; font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px;">Items Ordered</span>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <tr style="background-color: #f0fdfa;">
            <td style="padding: 10px 16px; color: #0d9488; font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;">Item</td>
            <td style="padding: 10px 16px; color: #0d9488; font-size: 12px; font-weight: 600; text-transform: uppercase; text-align: center; border-bottom: 1px solid #e2e8f0;">Qty</td>
            <td style="padding: 10px 16px; color: #0d9488; font-size: 12px; font-weight: 600; text-transform: uppercase; text-align: right; border-bottom: 1px solid #e2e8f0;">Price</td>
        </tr>
        @foreach($order->items as $index => $item)
        <tr style="{{ $index % 2 !== 0 ? 'background-color: #f8fafc;' : '' }}">
            <td style="padding: 10px 16px; color: #334155; font-size: 13px;{{ !$loop->last ? ' border-bottom: 1px solid #e2e8f0;' : '' }}">
                {{ $item->product_name }}
                @if($item->variations && $item->variations->count() > 0)
                    @foreach($item->variations as $variation)
                    <br /><span style="color: #64748b; font-size: 12px;">&nbsp;&nbsp;{{ $variation->variation_group_name }}: {{ $variation->option_name }}@if($variation->price_modifier > 0) (+&#8369;{{ number_format($variation->price_modifier, 2) }})@endif</span>
                    @endforeach
                @endif
                @if($item->itemAddons && $item->itemAddons->count() > 0)
                    @foreach($item->itemAddons as $addon)
                    <br /><span style="color: #64748b; font-size: 12px;">&nbsp;&nbsp;+ {{ $addon->addon_name }} (&#8369;{{ number_format($addon->addon_price, 2) }})</span>
                    @endforeach
                @endif
                @if($item->notes)
                    <br /><span style="color: #94a3b8; font-size: 12px; font-style: italic;">&nbsp;&nbsp;Note: {{ $item->notes }}</span>
                @endif
            </td>
            <td style="padding: 10px 16px; color: #334155; font-size: 13px; text-align: center; vertical-align: top;{{ !$loop->last ? ' border-bottom: 1px solid #e2e8f0;' : '' }}">{{ $item->quantity }}</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right; vertical-align: top;{{ !$loop->last ? ' border-bottom: 1px solid #e2e8f0;' : '' }}">&#8369;{{ number_format($item->subtotal, 2) }}</td>
        </tr>
        @endforeach
        </table>

        {{-- Order Summary --}}
        <span style="color: #0f172a; font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px;">Order Summary</span>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <tr style="background-color: #f8fafc;">
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Subtotal</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right; border-bottom: 1px solid #e2e8f0;">&#8369;{{ number_format($order->subtotal, 2) }}</td>
        </tr>
        @if($order->discount_amount > 0)
        <tr>
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Discount</td>
            <td style="padding: 10px 16px; color: #dc2626; font-size: 13px; font-weight: 600; text-align: right; border-bottom: 1px solid #e2e8f0;">-&#8369;{{ number_format($order->discount_amount, 2) }}</td>
        </tr>
        @endif
        @if($order->promotion_discount > 0)
        <tr style="background-color: #f8fafc;">
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px; border-bottom: 1px solid #e2e8f0;">
                Promo{{ $order->promotion ? ' (' . $order->promotion->code . ')' : '' }}
            </td>
            <td style="padding: 10px 16px; color: #dc2626; font-size: 13px; font-weight: 600; text-align: right; border-bottom: 1px solid #e2e8f0;">-&#8369;{{ number_format($order->promotion_discount, 2) }}</td>
        </tr>
        @endif
        @if($order->tax_amount > 0)
        <tr>
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Tax</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right; border-bottom: 1px solid #e2e8f0;">&#8369;{{ number_format($order->tax_amount, 2) }}</td>
        </tr>
        @endif
        <tr style="background-color: #f0fdfa;">
            <td style="padding: 14px 16px; color: #0d9488; font-size: 15px; font-weight: 700;">Total</td>
            <td style="padding: 14px 16px; color: #0d9488; font-size: 15px; font-weight: 700; text-align: right;">&#8369;{{ number_format($order->total, 2) }}</td>
        </tr>
        </table>

        {{-- Payment Details --}}
        @if($order->payments && $order->payments->count() > 0)
        <span style="color: #0f172a; font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px;">Payment Details</span>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        @foreach($order->payments as $payment)
        <tr style="background-color: #f8fafc;">
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Method</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right; border-bottom: 1px solid #e2e8f0;">{{ $payment->method->label() }}</td>
        </tr>
        <tr>
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Amount Paid</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right; border-bottom: 1px solid #e2e8f0;">&#8369;{{ number_format($payment->amount, 2) }}</td>
        </tr>
        @if($payment->method === \App\Enums\PaymentMethod::Cash)
        @if($payment->amount_tendered)
        <tr style="background-color: #f8fafc;">
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Tendered</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right; border-bottom: 1px solid #e2e8f0;">&#8369;{{ number_format($payment->amount_tendered, 2) }}</td>
        </tr>
        @endif
        @if($payment->change_amount > 0)
        <tr>
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px;{{ !$loop->last ? ' border-bottom: 1px solid #e2e8f0;' : '' }}">Change</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;{{ !$loop->last ? ' border-bottom: 1px solid #e2e8f0;' : '' }}">&#8369;{{ number_format($payment->change_amount, 2) }}</td>
        </tr>
        @endif
        @endif
        @if($payment->reference_number)
        <tr style="background-color: #f8fafc;">
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px;{{ !$loop->last ? ' border-bottom: 1px solid #e2e8f0;' : '' }}">Reference #</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;{{ !$loop->last ? ' border-bottom: 1px solid #e2e8f0;' : '' }}">{{ $payment->reference_number }}</td>
        </tr>
        @endif
        @endforeach
        </table>
        @endif

        {{-- View Receipt Button --}}
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 24px;">
        <tr>
            <td align="center">
                <a href="{{ $receiptUrl }}" style="display: inline-block; background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; padding: 12px 32px; border-radius: 6px;">View Receipt Online</a>
            </td>
        </tr>
        </table>

        @php
            $footerMessage = $tenant->data['receipt_footer'] ?? null;
        @endphp
        <p style="margin: 0; color: #334155; font-size: 15px; line-height: 1.6; text-align: center;">
            {{ $footerMessage ?? 'Thank you for your purchase!' }}
        </p>

    </td>
    </tr>

    {{-- Footer --}}
    <tr>
    <td style="background-color: #f8fafc; padding: 20px 40px; text-align: center; border-radius: 0 0 8px 8px; border: 1px solid #e2e8f0; border-top: none;">
        <p style="margin: 0; color: #94a3b8; font-size: 13px;">&copy; {{ date('Y') }} OmniPOS. All rights reserved.</p>
    </td>
    </tr>

</table>
</td>
</tr>
</table>

</body>
</html>
