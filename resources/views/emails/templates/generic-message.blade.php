@extends('emails.layouts.app')

@section('content')
    <h2 style="margin:0 0 12px;font-size:22px;color:#0f172a;">{{ $heading ?? 'Notification' }}</h2>

    @foreach(($messageLines ?? []) as $line)
        <p style="margin:0 0 10px;font-size:14px;line-height:1.7;color:#334155;">{{ $line }}</p>
    @endforeach

    @if(!empty($actionText) && !empty($actionUrl))
        <p style="margin:18px 0 0;">
            <a href="{{ $actionUrl }}"
               style="display:inline-block;padding:10px 16px;border-radius:8px;background:{{ $brand['primary_color'] ?? '#1a9e99' }};color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">
                {{ $actionText }}
            </a>
        </p>
    @endif

    @if(!empty($meta) && is_array($meta))
        <div style="margin-top:18px;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
            @foreach($meta as $label => $value)
                <p style="margin:0 0 6px;font-size:13px;color:#0f172a;">
                    <strong>{{ $label }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}
                </p>
            @endforeach
        </div>
    @endif
@endsection
