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

        <h2 style="margin: 0 0 20px; color: #0f172a; font-size: 20px; font-weight: 600;">Shift Started</h2>

        <p style="margin: 0 0 16px; color: #334155; font-size: 15px; line-height: 1.6;">Hello {{ $ownerName }},</p>

        <p style="margin: 0 0 24px; color: #334155; font-size: 15px; line-height: 1.6;">
            <strong>{{ $operatorName }}</strong> has started their shift at <strong>{{ $branchName }}</strong>.
        </p>

        {{-- Shift Details --}}
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f0fdfa; border-radius: 8px; border: 1px solid #ccfbf1; margin-bottom: 24px;">
        <tr>
            <td style="padding: 20px;">
                <span style="color: #0d9488; font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px;">Shift Details</span>
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td style="padding-bottom: 6px; color: #64748b; font-size: 13px;">Shift In:</td>
                    <td style="padding-bottom: 6px; color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">{{ $openedAt }}</td>
                </tr>
                <tr>
                    <td style="color: #64748b; font-size: 13px;">Starting Cash:</td>
                    <td style="color: #0f172a; font-size: 13px; font-weight: 600; text-align: right;">&#8369;{{ number_format($startingCash, 2) }}</td>
                </tr>
                </table>
            </td>
        </tr>
        </table>

        <p style="margin: 0 0 16px; color: #334155; font-size: 15px; line-height: 1.6;">
            You can view shift details in your OmniPOS dashboard.
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
