@extends('emails.layouts.app')

@section('content')
    <h2 style="margin:0 0 12px;font-size:22px;color:#0f172a;">New Contact Inquiry</h2>
    <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#334155;">
        A new inquiry was submitted through your frontend contact flow.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;width:160px;">Name</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;font-weight:600;">{{ $contact['full_name'] ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;">Email</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;">
                <a href="mailto:{{ $contact['email_address'] ?? '' }}" style="color:{{ $brand['primary_color'] ?? '#1a9e99' }};text-decoration:none;">
                    {{ $contact['email_address'] ?? '-' }}
                </a>
            </td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;">Phone</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;">{{ $contact['phone_number'] ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;">Company</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;">{{ $contact['company'] ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;">Service</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;">{{ $contact['service_interested'] ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;font-size:13px;color:#64748b;">Subject</td>
            <td style="padding:8px 0;font-size:14px;color:#0f172a;">{{ $contact['subject'] ?? '-' }}</td>
        </tr>
    </table>

    <div style="margin-top:20px;padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
        <p style="margin:0 0 6px;font-size:13px;color:#64748b;">Message</p>
        <p style="margin:0;font-size:14px;line-height:1.7;color:#0f172a;white-space:pre-line;">{{ $contact['message'] ?? '' }}</p>
    </div>
@endsection
