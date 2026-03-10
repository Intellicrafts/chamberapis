<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmed — {{ $brand['name'] ?? 'MeraBakil' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f5f5;padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.08);">

                <!-- ═══ HEADER ═══ -->
                <tr>
                    <td style="background:linear-gradient(135deg,#1a9e99 0%,#0d6e6a 100%);padding:36px 40px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <span style="color:#ffffff;font-size:18px;font-weight:800;">⚖️ {{ $brand['name'] ?? 'MeraBakil' }}</span>
                                </td>
                                <td align="right">
                                    <span style="background:rgba(255,255,255,0.2);color:#ffffff;font-size:11px;font-weight:700;padding:5px 12px;border-radius:20px;letter-spacing:1px;text-transform:uppercase;">📅 Confirmed</span>
                                </td>
                            </tr>
                        </table>
                        <!-- Hero Icon -->
                        <div align="center" style="padding:30px 0 0;">
                            <div style="background:rgba(255,255,255,0.15);display:inline-block;width:80px;height:80px;border-radius:50%;text-align:center;line-height:80px;font-size:40px;margin-bottom:-10px;">📅</div>
                        </div>
                    </td>
                </tr>

                <!-- ═══ SUCCESS BANNER ═══ -->
                <tr>
                    <td style="background:linear-gradient(135deg,#1a9e99 0%,#0d6e6a 100%);padding:20px 40px 36px;">
                        <h1 style="margin:0;text-align:center;font-size:26px;font-weight:800;color:#ffffff;line-height:1.3;">
                            Appointment Confirmed! ✅
                        </h1>
                        <p style="margin:10px 0 0;text-align:center;font-size:14px;color:rgba(255,255,255,0.8);">
                            {{ $isLawyerRecipient ?? false ? 'A new consultation has been assigned to you.' : 'Your legal consultation is booked successfully.' }}
                        </p>
                    </td>
                </tr>

                <!-- ═══ APPOINTMENT CARD ═══ -->
                <tr>
                    <td style="padding:32px 40px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#f0fafa,#e8f7f7);border:2px solid #c8eaea;border-radius:16px;padding:28px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#1a9e99;">📋 Appointment Details</p>
                                    <!-- Date & Time Row -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px;">
                                        <tr>
                                            <td width="50%" style="vertical-align:top;padding-right:12px;">
                                                <table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;padding:14px;">
                                                    <tr><td style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:1px;padding-bottom:4px;">📆 Date</td></tr>
                                                    <tr><td style="font-size:16px;color:#0f172a;font-weight:700;">{{ $appointment['appointment_date'] ?? 'To be confirmed' }}</td></tr>
                                                </table>
                                            </td>
                                            <td width="50%" style="vertical-align:top;padding-left:12px;">
                                                <table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;padding:14px;">
                                                    <tr><td style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:1px;padding-bottom:4px;">🕐 Time</td></tr>
                                                    <tr><td style="font-size:16px;color:#0f172a;font-weight:700;">{{ $appointment['appointment_time_formatted'] ?? '' }}</td></tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- Details List -->
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="font-size:13px;color:#475569;padding:7px 0;border-bottom:1px solid rgba(26,158,153,0.15);">👤 Client Name</td>
                                            <td style="font-size:13px;color:#0f172a;font-weight:600;text-align:right;padding:7px 0;border-bottom:1px solid rgba(26,158,153,0.15);">{{ $appointment['user_name'] ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px;color:#475569;padding:7px 0;border-bottom:1px solid rgba(26,158,153,0.15);">⚖️ Advocate</td>
                                            <td style="font-size:13px;color:#0f172a;font-weight:600;text-align:right;padding:7px 0;border-bottom:1px solid rgba(26,158,153,0.15);">{{ $appointment['lawyer_name'] ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px;color:#475569;padding:7px 0;border-bottom:1px solid rgba(26,158,153,0.15);">⏱️ Duration</td>
                                            <td style="font-size:13px;color:#0f172a;font-weight:600;text-align:right;padding:7px 0;border-bottom:1px solid rgba(26,158,153,0.15);">{{ $appointment['duration_minutes'] ?? '—' }} minutes</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px;color:#475569;padding:7px 0;">📌 Status</td>
                                            <td style="text-align:right;padding:7px 0;">
                                                <span style="background:#dcfce7;color:#166534;font-size:12px;font-weight:700;padding:3px 12px;border-radius:20px;text-transform:capitalize;">
                                                    ✅ {{ $appointment['status'] ?? 'Scheduled' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                @if(!empty($appointment['meeting_link']))
                <!-- ═══ MEETING LINK ═══ -->
                <tr>
                    <td style="padding:20px 40px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:18px 20px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#92400e;">🔗 Your Chamber / Meeting Link</p>
                                    <a href="{{ $appointment['meeting_link'] }}"
                                       style="color:#1a9e99;font-size:13px;word-break:break-all;">{{ $appointment['meeting_link'] }}</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endif

                <!-- ═══ INFO NOTICE ═══ -->
                <tr>
                    <td style="padding:20px 40px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:12px;padding:18px 20px;border-left:4px solid #1a9e99;">
                            <tr>
                                <td style="font-size:13px;color:#475569;line-height:1.7;">
                                    💡 <strong>Reminder:</strong> You'll also receive a reminder email 5 minutes before your session starts. Please ensure you are ready and that your internet connection is stable.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- ═══ CTA ═══ -->
                <tr>
                    <td align="center" style="padding:32px 40px 40px;">
                        <a href="{{ $brand['url'] ?? 'https://merabakil.com' }}"
                           style="display:inline-block;background:linear-gradient(135deg,#1a9e99,#0d6e6a);color:#ffffff;font-size:15px;font-weight:700;padding:14px 40px;border-radius:50px;text-decoration:none;box-shadow:0 4px 20px rgba(26,158,153,0.4);">
                            📅 &nbsp;View My Appointments
                        </a>
                    </td>
                </tr>

            </table>

            <!-- FOOTER -->
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;margin-top:20px;">
                <tr>
                    <td align="center" style="padding:0 20px 32px;">
                        <p style="margin:0 0 6px;font-size:13px;color:#64748b;">
                            Questions? <a href="mailto:{{ $brand['support_email'] ?? 'info@merabakil.com' }}" style="color:#1a9e99;text-decoration:none;">{{ $brand['support_email'] ?? 'info@merabakil.com' }}</a>
                        </p>
                        <p style="margin:0;font-size:12px;color:#94a3b8;">
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
