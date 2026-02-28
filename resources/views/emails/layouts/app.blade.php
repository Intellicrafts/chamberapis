<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brand['name'] ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f7;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7f7;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #dbe4e4;">
                    <tr>
                        <td style="padding:20px 24px;background:{{ $brand['primary_color'] ?? '#1a9e99' }};color:#ffffff;">
                            <table width="100%" role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="font-size:20px;font-weight:700;">
                                        {{ $brand['name'] ?? config('app.name') }}
                                    </td>
                                    <td align="right" style="font-size:12px;opacity:0.9;">
                                        Trusted Legal Support
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 24px 18px;">
                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#475569;">
                                Need help? Reach us at
                                <a href="mailto:{{ $brand['support_email'] ?? config('mail.from.address') }}" style="color:{{ $brand['primary_color'] ?? '#1a9e99' }};text-decoration:none;">
                                    {{ $brand['support_email'] ?? config('mail.from.address') }}
                                </a>
                            </p>
                            <p style="margin:8px 0 0;font-size:12px;color:#64748b;">
                                {{ $brand['name'] ?? config('app.name') }} &copy; {{ $year ?? date('Y') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
