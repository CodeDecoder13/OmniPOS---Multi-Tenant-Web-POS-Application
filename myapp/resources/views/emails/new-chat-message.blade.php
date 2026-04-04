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

        <h2 style="margin: 0 0 20px; color: #0f172a; font-size: 20px; font-weight: 600;">New Chat Message</h2>

        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f0fdfa; border-radius: 8px; border: 1px solid #ccfbf1; margin-bottom: 24px;">
        <tr>
            <td style="padding: 20px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td style="padding-bottom: 10px;">
                        <span style="color: #64748b; font-size: 13px; font-weight: 500;">From</span><br />
                        <span style="color: #0f172a; font-size: 15px; font-weight: 600;">{{ $userName }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom: 10px;">
                        <span style="color: #64748b; font-size: 13px; font-weight: 500;">Email</span><br />
                        <a href="mailto:{{ $userEmail }}" style="color: #0d9488; font-size: 15px; text-decoration: none;">{{ $userEmail }}</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom: {{ $chatSubject ? '10px' : '0' }};">
                        <span style="color: #64748b; font-size: 13px; font-weight: 500;">Tenant</span><br />
                        <span style="color: #0f172a; font-size: 15px;">{{ $tenantName }}</span>
                    </td>
                </tr>
                @if($chatSubject)
                <tr>
                    <td>
                        <span style="color: #64748b; font-size: 13px; font-weight: 500;">Subject</span><br />
                        <span style="color: #0f172a; font-size: 15px;">{{ $chatSubject }}</span>
                    </td>
                </tr>
                @endif
                </table>
            </td>
        </tr>
        </table>

        <div style="color: #64748b; font-size: 13px; font-weight: 500; margin-bottom: 8px;">Message</div>
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; color: #334155; font-size: 15px; line-height: 1.6; white-space: pre-wrap;">{{ $messageBody }}</div>

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
