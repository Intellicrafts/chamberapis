<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $brand['name'] ?? 'MeraBakil' }} — Lawyer Portal</title>
</head>
<body style="margin:0;padding:0;background-color:#0d1b1e;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0d1b1e;padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#111f22;border-radius:20px;overflow:hidden;box-shadow:0 8px 60px rgba(26,158,153,0.2);border:1px solid rgba(26,158,153,0.2);">

                <!-- ═══ HEADER ═══ -->
                <tr>
                    <td style="background:linear-gradient(135deg,#0d6e6a 0%,#1a9e99 50%,#10b8b2 100%);padding:40px 40px 0;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <span style="color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-0.5px;">⚖️ {{ $brand['name'] ?? 'MeraBakil' }}</span>
                                    <span style="display:block;color:rgba(255,255,255,0.7);font-size:12px;font-weight:500;letter-spacing:2px;text-transform:uppercase;margin-top:4px;">Advocate Portal</span>
                                </td>
                                <td align="right">
                                    <span style="background:rgba(255,255,255,0.15);color:#ffffff;font-size:12px;font-weight:600;padding:5px 14px;border-radius:20px;letter-spacing:0.5px;">✅ Verified Partner</span>
                                </td>
                            </tr>
                        </table>
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=560&h=220&q=80"
                             alt="Legal Professional" width="100%"
                             style="max-width:520px;border-radius:12px 12px 0 0;display:block;margin-top:28px;border:0;">
                    </td>
                </tr>

                <!-- ═══ WELCOME HERO ═══ -->
                <tr>
                    <td style="padding:36px 40px 4px;">
                        <h1 style="margin:0 0 8px;font-size:26px;font-weight:800;color:#ffffff;line-height:1.3;">
                            Welcome, Advocate {{ $lawyerName ?? 'there' }} 🎉
                        </h1>
                        <p style="margin:0 0 6px;font-size:14px;font-weight:600;color:#1a9e99;text-transform:uppercase;letter-spacing:1px;">
                            {{ $specialization ?? 'Legal Expert' }}
                        </p>
                        <p style="margin:12px 0 0;font-size:15px;color:#94a3b8;line-height:1.7;">
                            Your advocate account on <strong style="color:#1a9e99;">{{ $brand['name'] ?? 'MeraBakil' }}</strong> is live! Clients are waiting to connect with experts like you. Start accepting consultations today.
                        </p>
                    </td>
                </tr>

                <!-- ═══ STATS BAR ═══ -->
                <tr>
                    <td style="padding:24px 40px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:rgba(26,158,153,0.1);border:1px solid rgba(26,158,153,0.25);border-radius:14px;padding:0;">
                            <tr>
                                <td width="33%" align="center" style="padding:20px 10px;border-right:1px solid rgba(26,158,153,0.2);">
                                    <div style="font-size:26px;font-weight:800;color:#1a9e99;line-height:1;">0</div>
                                    <div style="font-size:12px;color:#64748b;margin-top:4px;">Consultations</div>
                                </td>
                                <td width="33%" align="center" style="padding:20px 10px;border-right:1px solid rgba(26,158,153,0.2);">
                                    <div style="font-size:26px;font-weight:800;color:#d4891f;line-height:1;">⭐ –</div>
                                    <div style="font-size:12px;color:#64748b;margin-top:4px;">Rating</div>
                                </td>
                                <td width="33%" align="center" style="padding:20px 10px;">
                                    <div style="font-size:26px;font-weight:800;color:#10b981;line-height:1;">✔</div>
                                    <div style="font-size:12px;color:#64748b;margin-top:4px;">Verified</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- ═══ ACCOUNT DETAILS ═══ -->
                <tr>
                    <td style="padding:0 40px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a2d30;border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:24px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 16px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#1a9e99;">Account Information</p>
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="font-size:13px;color:#64748b;padding:6px 0;width:45%;">📧 Email</td>
                                            <td style="font-size:13px;color:#e2e8f0;font-weight:500;">{{ $lawyerEmail ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px;color:#64748b;padding:6px 0;">🏛️ Bar Enrollment</td>
                                            <td style="font-size:13px;color:#e2e8f0;font-weight:500;">{{ $enrollmentNo ?? 'Pending' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px;color:#64748b;padding:6px 0;">⚖️ Specialization</td>
                                            <td style="font-size:13px;color:#e2e8f0;font-weight:500;">{{ $specialization ?? 'General' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:13px;color:#64748b;padding:6px 0;">📅 Joined On</td>
                                            <td style="font-size:13px;color:#e2e8f0;font-weight:500;">{{ $joinedAt ?? now()->format('d M Y') }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- ═══ PENDING VERIFICATION NOTICE ═══ -->
                <tr>
                    <td style="padding:0 40px 28px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:rgba(212,137,31,0.1);border:1px solid rgba(212,137,31,0.3);border-radius:12px;padding:18px 20px;">
                            <tr>
                                <td style="font-size:13px;color:#d4891f;font-weight:700;padding-bottom:6px;">⏳ Verification Pending</td>
                            </tr>
                            <tr>
                                <td style="font-size:13px;color:#94a3b8;line-height:1.6;">
                                    Your account is under review. Once approved by our team, you will be able to accept appointment bookings from clients. This typically takes 1–2 business days.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- ═══ CTA ═══ -->
                <tr>
                    <td align="center" style="padding:4px 40px 40px;">
                        <a href="{{ $brand['url'] ?? 'https://merabakil.com' }}"
                           style="display:inline-block;background:linear-gradient(135deg,#1a9e99,#0d6e6a);color:#ffffff;font-size:16px;font-weight:700;padding:15px 44px;border-radius:50px;text-decoration:none;letter-spacing:0.3px;box-shadow:0 4px 24px rgba(26,158,153,0.5);">
                            🏛️ &nbsp;Open Your Dashboard
                        </a>
                    </td>
                </tr>

            </table>

            <!-- FOOTER -->
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;margin-top:20px;">
                <tr>
                    <td align="center" style="padding:0 20px 32px;">
                        <p style="margin:0 0 6px;font-size:13px;color:#475569;">
                            Questions? <a href="mailto:{{ $brand['support_email'] ?? 'info@merabakil.com' }}" style="color:#1a9e99;text-decoration:none;">{{ $brand['support_email'] ?? 'info@merabakil.com' }}</a>
                        </p>
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
