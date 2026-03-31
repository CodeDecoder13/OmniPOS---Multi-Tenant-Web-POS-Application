@props(['url'])
<tr>
<td class="header" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 50%, #2dd4bf 100%); padding: 30px 0; text-align: center;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none; color: #ffffff;">
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
</a>
</td>
</tr>
