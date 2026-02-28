@extends('emails.layouts.app')

@section('content')
    <h2 style="margin:0 0 12px;font-size:22px;color:#0f172a;">Exception Report</h2>
    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#334155;">
        A new exception has been logged in the application.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
        @foreach(($errorData ?? []) as $key => $value)
            <tr>
                <td style="padding:8px 0;font-size:13px;color:#64748b;width:140px;text-transform:capitalize;">{{ str_replace('_', ' ', $key) }}</td>
                <td style="padding:8px 0;font-size:13px;line-height:1.6;color:#0f172a;word-break:break-word;">
                    {{ is_array($value) ? json_encode($value) : $value }}
                </td>
            </tr>
        @endforeach
    </table>
@endsection
