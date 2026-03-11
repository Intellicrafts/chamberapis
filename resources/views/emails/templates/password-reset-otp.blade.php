@extends('emails.layouts.app')

@section('content')
<div style="text-align: center; margin-bottom: 24px;">
    <h2 style="margin: 0 0 8px; font-size: 24px; color: #0f172a; font-weight: 700;">{{ $title ?? 'Password Reset Request' }}</h2>
    <p style="margin: 0; font-size: 16px; color: #475569;">Hello <strong style="color: #0f172a;">{{ $userName ?? 'User' }}</strong>,</p>
</div>

<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
    <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6; color: #334155; text-align: center;">
        We received a request to reset the password for your MeraBakil account. Please use the secure verification code below to proceed:
    </p>

    <div style="background: #f8fafc; border: 2px dashed #94a3b8; border-radius: 8px; padding: 24px; text-align: center; margin-bottom: 20px;">
        <span style="display: inline-block; font-size: 38px; letter-spacing: 14px; font-weight: 800; color: #0f172a; font-family: Monaco, Consolas, monospace;">{{ $otp }}</span>
    </div>

    <div style="text-align: center; background: #fef2f2; border: 1px solid #fecaca; padding: 12px; border-radius: 6px;">
        <p style="margin: 0; font-size: 14px; color: #dc2626; font-weight: 600;">
            ⏳ This code will expire in {{ $expiresInMinutes ?? 1 }} minute(s).
        </p>
    </div>
</div>

<div style="background: #f1f5f9; border-radius: 8px; padding: 16px;">
    <p style="margin: 0 0 10px; font-size: 14px; font-weight: 600; color: #0f172a;">Security Notice:</p>
    <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #475569; line-height: 1.6;">
        <li>Never share this OTP with anyone, including MeraBakil staff.</li>
        <li>If you did not request a password reset, you can safely ignore this email.</li>
    </ul>
</div>
@endsection
