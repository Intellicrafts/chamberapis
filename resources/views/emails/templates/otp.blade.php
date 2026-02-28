@extends('emails.layouts.app')

@section('content')
    <h2 style="margin:0 0 12px;font-size:22px;color:#0f172a;">{{ $title ?? 'Your OTP Code' }}</h2>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
        Use the OTP below to continue your request. This code is valid for {{ $expiresInMinutes ?? 10 }} minutes.
    </p>

    <div style="margin:18px 0;padding:16px;background:#f1f5f9;border:1px dashed #94a3b8;border-radius:12px;text-align:center;">
        <span style="display:inline-block;font-size:30px;letter-spacing:6px;font-weight:700;color:#0f172a;">{{ $otp }}</span>
    </div>

    <p style="margin:0;font-size:13px;color:#64748b;">
        If you did not request this, you can safely ignore this email.
    </p>
@endsection
