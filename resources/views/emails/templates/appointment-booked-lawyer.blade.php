@extends('emails.layouts.app')

@section('content')
    <h2 style="margin:0 0 10px;font-size:24px;color:#0f172a;">New Client Appointment</h2>
    <p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#334155;">
        Hello {{ $appointment['lawyer_name'] ?? 'Counsel' }}, a new appointment has been scheduled for you.
    </p>

    <div style="padding:16px;background:linear-gradient(135deg,#fff7ed,#f8fafc);border:1px solid #f6dfc4;border-radius:12px;margin-bottom:18px;">
        <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#b45309;">Appointment Reference</p>
        <p style="margin:0;font-size:18px;font-weight:700;color:#0f172a;">#{{ $appointment['id'] ?? '-' }}</p>
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;width:170px;">Client</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;font-weight:600;">{{ $appointment['user_name'] ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;">Client Email</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;">{{ $appointment['user_email'] ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;">Date</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;">{{ $appointment['appointment_date'] ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;">Time</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;">{{ $appointment['appointment_time_formatted'] ?? '-' }} ({{ $appointment['timezone'] ?? 'UTC' }})</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;">Duration</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;">{{ $appointment['duration_minutes'] ?? '-' }} minutes</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;">Status</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;text-transform:capitalize;">{{ $appointment['status'] ?? 'scheduled' }}</td>
        </tr>
    </table>

    @if(!empty($appointment['meeting_link']))
        <p style="margin:20px 0 0;">
            <a href="{{ $appointment['meeting_link'] }}"
               style="display:inline-block;padding:11px 18px;border-radius:8px;background:{{ $brand['accent_color'] ?? '#d4891f' }};color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">
                Open Meeting Link
            </a>
        </p>
    @endif
@endsection
