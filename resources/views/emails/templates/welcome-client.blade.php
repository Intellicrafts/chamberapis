<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $brand['name'] ?? 'MeraBakil' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f5f5;padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.08);">

                <!-- ═══ HEADER BANNER ═══ -->
                <tr>
                    <td style="background:linear-gradient(135deg,#1a9e99 0%,#0d6e6a 60%,#0a5a56 100%);padding:0;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding:36px 40px 0;">
                                    <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="background:rgba(255,255,255,0.15);border-radius:10px;padding:8px 16px;">
                                                @if(!empty($brand['logo_url']))
                                                    <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] ?? 'MeraBakil' }}" height="26" style="display:block;border:none;">
                                                @else
                                                    <span style="color:#ffffff;font-size:20px;font-weight:900;letter-spacing:-0.5px;">⚖️ {{ $brand['name'] ?? 'MeraBakil' }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding:30px 40px 0;">
                                    <img src="https://images.unsplash.com/photo-1589829085413-56de8ae18c73?auto=format&fit=crop&w=560&h=240&q=80"
                                         alt="Legal Experts" width="100%"
                                         style="max-width:520px;border-radius:14px 14px 0 0;display:block;border:0;">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- ═══ WELCOME HERO ═══ -->
                <tr>
                    <td style="padding:40px 40px 0;">
                        <h1 style="margin:0 0 12px;font-size:28px;font-weight:800;color:#0f172a;line-height:1.2;">
                            Welcome aboard, {{ $userName ?? 'there' }}! 👋
                        </h1>
                        <p style="margin:0 0 24px;font-size:16px;color:#475569;line-height:1.7;">
                            Your account has been successfully created on <strong style="color:#1a9e99;">{{ $brand['name'] ?? 'MeraBakil' }}</strong> — India's most trusted legal consultation platform. You're now one step closer to getting expert legal guidance.
                        </p>
                    </td>
                </tr>

                <!-- ═══ FEATURE CARDS ═══ -->
                <tr>
                    <td style="padding:0 40px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <!-- Card 1 -->
                                <td width="33%" style="padding:0 6px 0 0;vertical-align:top;">
                                    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e8f4f4;border-radius:12px;padding:20px;">
                                        <tr><td align="center" style="font-size:28px;padding-bottom:10px;">🔍</td></tr>
                                        <tr><td style="font-size:13px;font-weight:700;color:#0f172a;text-align:center;padding-bottom:6px;">Find Lawyers</td></tr>
                                        <tr><td style="font-size:12px;color:#64748b;text-align:center;line-height:1.5;">Search verified legal experts by specialization</td></tr>
                                    </table>
                                </td>
                                <!-- Card 2 -->
                                <td width="33%" style="padding:0 3px;vertical-align:top;">
                                    <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff8f0;border:1px solid #fde8c8;border-radius:12px;padding:20px;">
                                        <tr><td align="center" style="font-size:28px;padding-bottom:10px;">📅</td></tr>
                                        <tr><td style="font-size:13px;font-weight:700;color:#0f172a;text-align:center;padding-bottom:6px;">Book Sessions</td></tr>
                                        <tr><td style="font-size:12px;color:#64748b;text-align:center;line-height:1.5;">Schedule consultations at your convenience</td></tr>
                                    </table>
                                </td>
                                <!-- Card 3 -->
                                <td width="33%" style="padding:0 0 0 6px;vertical-align:top;">
                                    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e8f4f4;border-radius:12px;padding:20px;">
                                        <tr><td align="center" style="font-size:28px;padding-bottom:10px;">🛡️</td></tr>
                                        <tr><td style="font-size:13px;font-weight:700;color:#0f172a;text-align:center;padding-bottom:6px;">Stay Protected</td></tr>
                                        <tr><td style="font-size:12px;color:#64748b;text-align:center;line-height:1.5;">Get the right legal advice, when you need it</td></tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- ═══ ACCOUNT INFO BOX ═══ -->
                <tr>
                    <td style="padding:28px 40px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#f0fafa,#e8f7f7);border:1px solid #c8eaea;border-radius:14px;padding:24px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 14px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#1a9e99;">Your Account Details</p>
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="font-size:14px;color:#475569;padding:5px 0;width:40%;">📧 Registered Email</td>
                                            <td style="font-size:14px;color:#0f172a;font-weight:600;">{{ $userEmail ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:14px;color:#475569;padding:5px 0;">👤 Account Type</td>
                                            <td style="font-size:14px;color:#0f172a;font-weight:600;">Client / User</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:14px;color:#475569;padding:5px 0;">📅 Joined On</td>
                                            <td style="font-size:14px;color:#0f172a;font-weight:600;">{{ $joinedAt ?? now()->format('d M Y') }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- ═══ CTA BUTTON ═══ -->
                <tr>
                    <td align="center" style="padding:36px 40px;">
                        @php
                            $baseUrl = rtrim($brand['url'] ?? 'https://merabakil.com', '/');
                            $dashboardUrl = $baseUrl . '/user/dashboard';
                        @endphp
                        <a href="{{ $dashboardUrl }}"
                           style="display:inline-block;background:linear-gradient(135deg,#d4891f,#b8710f);color:#ffffff;font-size:16px;font-weight:700;padding:15px 40px;border-radius:50px;text-decoration:none;letter-spacing:0.3px;box-shadow:0 4px 20px rgba(212,137,31,0.4);">
                            🚀 &nbsp;Explore MeraBakil Now
                        </a>
                        <p style="margin:16px 0 0;font-size:13px;color:#94a3b8;">
                            Need help? Email us at
                            <a href="mailto:{{ $brand['support_email'] ?? 'info@merabakil.com' }}" style="color:#1a9e99;text-decoration:none;font-weight:600;">{{ $brand['support_email'] ?? 'info@merabakil.com' }}</a>
                        </p>
                    </td>
                </tr>

            </table>

            <!-- ═══ FOOTER ═══ -->
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;margin-top:24px;">
                <tr>
                    <td align="center" style="padding:0 20px 32px;">
                        <p style="margin:0 0 8px;font-size:13px;color:#94a3b8;">
                            © {{ date('Y') }} {{ $brand['name'] ?? 'MeraBakil' }}. All rights reserved.
                        </p>
                        <p style="margin:0;font-size:12px;color:#b0b8c8;">
                            You received this email because an account was created for this address.
                        </p>
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

</body>
</html>
