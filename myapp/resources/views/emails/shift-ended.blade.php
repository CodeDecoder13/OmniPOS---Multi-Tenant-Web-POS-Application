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

        <h2 style="margin: 0 0 20px; color: #0f172a; font-size: 20px; font-weight: 600;">Shift Ended</h2>

        <p style="margin: 0 0 16px; color: #334155; font-size: 15px; line-height: 1.6;">Hello {{ $ownerName }},</p>

        <p style="margin: 0 0 24px; color: #334155; font-size: 15px; line-height: 1.6;">
            <strong>{{ $operatorName }}</strong> has ended their shift at <strong>{{ $branchName }}</strong>.
        </p>

        {{-- Shift Times --}}
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f0fdfa; border-radius: 8px; border: 1px solid #ccfbf1; margin-bottom: 24px;">
        <tr>
            <td style="padding: 20px;">
                <span style="color: #0d9488; font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px;">Shift Times</span>
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td style="padding-bottom: 6px; color: #64748b; font-size: 13px;">Shift In:</td>
                    <td style="padding-bottom: 6px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $openedAt }}</td>
                </tr>
                <tr>
                    <td style="padding-bottom: 6px; color: #64748b; font-size: 13px;">Shift Out:</td>
                    <td style="padding-bottom: 6px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $closedAt }}</td>
                </tr>
                <tr>
                    <td style="color: #64748b; font-size: 13px;">Hours Rendered:</td>
                    <td style="color: #0d9488; font-size: 13px; font-weight: 600; text-align: right;">{{ $hoursRendered }}</td>
                </tr>
                </table>
            </td>
        </tr>
        </table>

        {{-- Sales Summary (2x2 grid) --}}
        <span style="color: #0f172a; font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px;">Sales Summary</span>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 24px;">
        <tr>
            <td width="50%" style="padding: 0 6px 12px 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                <tr>
                    <td style="padding: 16px;">
                        <span style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Total Sales</span>
                        <span style="color: #0f172a; font-size: 20px; font-weight: 700;">&#8369;{{ number_format($totalSales, 2) }}</span>
                    </td>
                </tr>
                </table>
            </td>
            <td width="50%" style="padding: 0 0 12px 6px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                <tr>
                    <td style="padding: 16px;">
                        <span style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Orders</span>
                        <span style="color: #0f172a; font-size: 20px; font-weight: 700;">{{ $totalOrders }}</span>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td width="50%" style="padding: 0 6px 0 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                <tr>
                    <td style="padding: 16px;">
                        <span style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Avg Order Value</span>
                        <span style="color: #0f172a; font-size: 20px; font-weight: 700;">&#8369;{{ number_format($avgOrderValue, 2) }}</span>
                    </td>
                </tr>
                </table>
            </td>
            <td width="50%" style="padding: 0 0 0 6px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                <tr>
                    <td style="padding: 16px;">
                        <span style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Voided</span>
                        <span style="color: #0f172a; font-size: 20px; font-weight: 700;">{{ $voidedCount }}</span>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        </table>

        {{-- Cash Reconciliation --}}
        <span style="color: #0f172a; font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px;">Cash Reconciliation</span>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <tr style="background-color: #f8fafc;">
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Starting Cash</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right; border-bottom: 1px solid #e2e8f0;">&#8369;{{ number_format($startingCash, 2) }}</td>
        </tr>
        <tr>
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Expected Cash</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right; border-bottom: 1px solid #e2e8f0;">&#8369;{{ number_format($expectedCash, 2) }}</td>
        </tr>
        <tr style="background-color: #f8fafc;">
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px; border-bottom: 1px solid #e2e8f0;">Actual Cash</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right; border-bottom: 1px solid #e2e8f0;">&#8369;{{ number_format($actualCash, 2) }}</td>
        </tr>
        <tr>
            <td style="padding: 10px 16px; color: #64748b; font-size: 13px;">Difference</td>
            <td style="padding: 10px 16px; font-size: 13px; font-weight: 700; text-align: right;
                @if($cashDifference > 0) color: #16a34a;
                @elseif($cashDifference < 0) color: #dc2626;
                @else color: #64748b;
                @endif
            ">
                @if($cashDifference > 0)+@endif&#8369;{{ number_format($cashDifference, 2) }}
            </td>
        </tr>
        </table>

        {{-- Payment Breakdown --}}
        @if(count($paymentBreakdown) > 0)
        <span style="color: #0f172a; font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px;">Payment Breakdown</span>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <tr style="background-color: #f0fdfa;">
            <td style="padding: 10px 16px; color: #0d9488; font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;">Method</td>
            <td style="padding: 10px 16px; color: #0d9488; font-size: 12px; font-weight: 600; text-transform: uppercase; text-align: center; border-bottom: 1px solid #e2e8f0;">Orders</td>
            <td style="padding: 10px 16px; color: #0d9488; font-size: 12px; font-weight: 600; text-transform: uppercase; text-align: right; border-bottom: 1px solid #e2e8f0;">Total Sales</td>
        </tr>
        @foreach($paymentBreakdown as $index => $payment)
        <tr style="{{ $index % 2 === 0 ? '' : 'background-color: #f8fafc;' }}">
            <td style="padding: 10px 16px; color: #334155; font-size: 13px;{{ $loop->last ? '' : ' border-bottom: 1px solid #e2e8f0;' }}">{{ $payment['method'] }}</td>
            <td style="padding: 10px 16px; color: #334155; font-size: 13px; text-align: center;{{ $loop->last ? '' : ' border-bottom: 1px solid #e2e8f0;' }}">{{ $payment['count'] }}</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;{{ $loop->last ? '' : ' border-bottom: 1px solid #e2e8f0;' }}">&#8369;{{ number_format($payment['total'], 2) }}</td>
        </tr>
        @endforeach
        </table>
        @endif

        {{-- Products Sold --}}
        @if(count($productsSold) > 0)
        <span style="color: #0f172a; font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px;">Products Sold</span>
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <tr style="background-color: #f0fdfa;">
            <td style="padding: 10px 16px; color: #0d9488; font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;">Product</td>
            <td style="padding: 10px 16px; color: #0d9488; font-size: 12px; font-weight: 600; text-transform: uppercase; text-align: center; border-bottom: 1px solid #e2e8f0;">Qty</td>
            <td style="padding: 10px 16px; color: #0d9488; font-size: 12px; font-weight: 600; text-transform: uppercase; text-align: right; border-bottom: 1px solid #e2e8f0;">Total</td>
        </tr>
        @foreach($productsSold as $index => $product)
        <tr style="{{ $index % 2 === 0 ? '' : 'background-color: #f8fafc;' }}">
            <td style="padding: 10px 16px; color: #334155; font-size: 13px;{{ $loop->last ? '' : ' border-bottom: 1px solid #e2e8f0;' }}">{{ $product['name'] }}</td>
            <td style="padding: 10px 16px; color: #334155; font-size: 13px; text-align: center;{{ $loop->last ? '' : ' border-bottom: 1px solid #e2e8f0;' }}">{{ $product['quantity'] }}</td>
            <td style="padding: 10px 16px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;{{ $loop->last ? '' : ' border-bottom: 1px solid #e2e8f0;' }}">&#8369;{{ number_format($product['total'], 2) }}</td>
        </tr>
        @endforeach
        </table>
        @endif

        <p style="margin: 0 0 16px; color: #334155; font-size: 15px; line-height: 1.6;">
            You can view the full shift details in your OmniPOS dashboard.
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
