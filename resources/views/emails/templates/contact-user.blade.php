@extends('emails.layouts.app')

@section('content')
    <h2 style="margin:0 0 12px;font-size:22px;color:#0f172a;">Thanks for contacting {{ $brand['name'] ?? config('app.name') }}</h2>
    <p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#334155;">
        Hi {{ $contact['full_name'] ?? 'there' }}, we received your message and our team will respond shortly.
    </p>

    <div style="margin-top:14px;padding:14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
        <p style="margin:0 0 6px;font-size:13px;color:#64748b;">Your message summary</p>
        <p style="margin:0 0 6px;font-size:14px;color:#0f172a;"><strong>Subject:</strong> {{ $contact['subject'] ?? 'General Inquiry' }}</p>
        <p style="margin:0;font-size:14px;line-height:1.6;color:#0f172a;white-space:pre-line;">{{ $contact['message'] ?? '' }}</p>
    </div>

    <p style="margin:16px 0 0;font-size:13px;color:#64748b;">
        For urgent support, email us at
        <a href="mailto:{{ $brand['support_email'] ?? config('mail.from.address') }}" style="color:{{ $brand['primary_color'] ?? '#1a9e99' }};text-decoration:none;">
            {{ $brand['support_email'] ?? config('mail.from.address') }}
        </a>.
    </p>
@endsection
