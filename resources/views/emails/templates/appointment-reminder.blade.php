<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Session Starts in 5 Minutes — {{ $brand['name'] ?? 'MeraBakil' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#0f1419;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f1419;padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#161d26;border-radius:20px;overflow:hidden;box-shadow:0 8px 60px rgba(212,137,31,0.2);border:1px solid rgba(212,137,31,0.2);">

                <!-- ═══ URGENT HEADER ═══ -->
                <tr>
                    <td style="background:linear-gradient(135deg,#92400e 0%,#b45309 40%,#d4891f 100%);padding:32px 40px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    @if(!empty($brand['logo_url']))
                                        <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] ?? 'MeraBakil' }}" height="26" style="display:block;border:none;">
                                    @else
                                        <span style="color:#ffffff;font-size:20px;font-weight:900;letter-spacing:-0.5px;">⚖️ {{ $brand['name'] ?? 'MeraBakil' }}</span>
                                    @endif
                                </td>
                                <td align="right">
                                    <span style="background:rgba(255,255,255,0.2);color:#ffffff;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;">🔔 Reminder</span>
                                </td>
                            </tr>
                        </table>
                        <!-- Countdown Section -->
                        <div align="center" style="padding:28px 0 0;">
                            <div style="background:rgba(255,255,255,0.15);display:inline-block;border-radius:16px;padding:16px 36px;margin-bottom:16px;">
                                <div style="font-size:52px;font-weight:900;color:#ffffff;line-height:1;letter-spacing:-2px;">5</div>
                                <div style="font-size:13px;color:rgba(255,255,255,0.8);font-weight:600;text-transform:uppercase;letter-spacing:2px;margin-top:4px;">MINUTES</div>
                            </div>
                            <h1 style="margin:8px 0 0;font-size:22px;font-weight:800;color:#ffffff;">Your Session Is About to Start!</h1>
                            <p style="margin:8px 0 0;font-size:14px;color:rgba(255,255,255,0.75);">Get ready — your consultation begins very soon</p>
                        </div>
                    </td>
                </tr>

                <!-- ═══ APPOINTMENT DETAILS ═══ -->
                <tr>
                    <td style="padding:32px 40px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#1e2a35;border:1px solid rgba(212,137,31,0.25);border-radius:14px;padding:24px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 18px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#d4891f;">📋 Session Details</p>
                                    <!-- Time Block -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="background:rgba(212,137,31,0.1);border:1px solid rgba(212,137,31,0.2);border-radius:10px;margin-bottom:18px;">
                                        <tr>
                                            <td width="50%" align="center" style="padding:14px;border-right:1px solid rgba(212,137,31,0.2);">
                                                <div style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">📆 Date</div>
                                                <div style="font-size:15px;color:#f1f5f9;font-weight:700;">{{ $appointment['appointment_date'] ?? '—' }}</div>
                                            </td>
                                            <td width="50%" align="center" style="padding:14px;">
                                                <div style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">🕐 Time</div>
                                                <div style="font-size:15px;color:#f1f5f9;font-weight:700;">{{ $appointment['appointment_time_formatted'] ?? '—' }}</div>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- People -->
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="font-size:13px;color:#64748b;padding:7px 0;border-bottom:1px solid rgba(255,255,255,0.06);">👤 Client</td>
                                            <td style="font-size:13px;color:#e2e8f0;font-weight:600;text-align:right;padding:7px 0;border-bottom:1px solid rgba(255,255,255,0.06);">{{ $appointment['user_name'] ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px;color:#64748b;padding:7px 0;border-bottom:1px solid rgba(255,255,255,0.06);">⚖️ Advocate</td>
                                            <td style="font-size:13px;color:#e2e8f0;font-weight:600;text-align:right;padding:7px 0;border-bottom:1px solid rgba(255,255,255,0.06);">{{ $appointment['lawyer_name'] ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px;color:#64748b;padding:7px 0;">⏱️ Duration</td>
                                            <td style="font-size:13px;color:#e2e8f0;font-weight:600;text-align:right;padding:7px 0;">{{ $appointment['duration_minutes'] ?? '—' }} minutes</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                @if(!empty($appointment['meeting_link']))
                <!-- ═══ JOIN NOW BLOCK ═══ -->
                <tr>
                    <td style="padding:20px 40px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a2d1e;border:1px solid rgba(16,185,129,0.3);border-radius:14px;padding:22px 24px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#10b981;">🟢 Session Link Ready</p>
                                    <p style="margin:0 0 14px;font-size:13px;color:#94a3b8;line-height:1.6;">Click the button below to instantly join your private consultation chamber.</p>
                                    <a href="{{ $appointment['meeting_link'] }}"
                                       style="display:inline-block;background:linear-gradient(135deg,#059669,#10b981);color:#ffffff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:8px;text-decoration:none;">
                                        🚀 &nbsp;Join Chamber Now
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endif

                <!-- ═══ CHECKLIST ═══ -->
                <tr>
                    <td style="padding:20px 40px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#1e2a35;border-radius:12px;padding:18px 20px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 14px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94a3b8;">✅ Quick Pre-Session Checklist</p>
                                    <table cellpadding="0" cellspacing="0">
                                        <tr><td style="font-size:13px;color:#cbd5e1;padding:5px 0;">✔️ &nbsp;Stable internet connection</td></tr>
                                        <tr><td style="font-size:13px;color:#cbd5e1;padding:5px 0;">✔️ &nbsp;Documents or case details ready</td></tr>
                                        <tr><td style="font-size:13px;color:#cbd5e1;padding:5px 0;">✔️ &nbsp;Microphone and camera working</td></tr>
                                        <tr><td style="font-size:13px;color:#cbd5e1;padding:5px 0;">✔️ &nbsp;Quiet, private environment</td></tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- ═══ CTA ═══ -->
                <tr>
                    <td align="center" style="padding:30px 40px 40px;">
                        @php
                            $baseUrl = $brand['url'] ?? 'https://merabakil.com';
                            $dashboardUrl = ($isLawyerRecipient ?? false) ? rtrim($baseUrl, '/') . '/lawyer/consultations' : rtrim($baseUrl, '/') . '/user/appointments';
                            $ctaText = ($isLawyerRecipient ?? false) ? '⚡  Go to My Consultations' : '⚡  Go to My Appointments';
                        @endphp
                        <a href="{{ $dashboardUrl }}"
                           style="display:inline-block;background:linear-gradient(135deg,#d4891f,#b8710f);color:#ffffff;font-size:15px;font-weight:700;padding:14px 40px;border-radius:50px;text-decoration:none;box-shadow:0 4px 24px rgba(212,137,31,0.5);">
                            {{ $ctaText }}
                        </a>
                        <p style="margin:14px 0 0;font-size:12px;color:#475569;">
                            Need help? <a href="mailto:{{ $brand['support_email'] ?? 'info@merabakil.com' }}" style="color:#d4891f;text-decoration:none;">Contact Support</a>
                        </p>
                    </td>
                </tr>

            </table>

            <!-- FOOTER -->
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;margin-top:20px;">
                <tr>
                    <td align="center" style="padding:0 20px 32px;">
                        <p style="margin:0;font-size:12px;color:#334155;">
                            © {{ date('Y') }} {{ $brand['name'] ?? 'MeraBakil' }}. All rights reserved.
                        </p>
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>
</body>
</html>
