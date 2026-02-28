<?php

return [
    'admin_recipients' => array_values(array_filter(array_map(
        static fn ($email) => trim($email),
        explode(',', (string) env('MAIL_ADMIN_RECIPIENTS', env('MAIL_FROM_ADDRESS', '')))
    ))),

    'brand' => [
        'name' => env('MAIL_BRAND_NAME', env('APP_NAME', 'MeraVakil')),
        'url' => env('MAIL_BRAND_URL', env('APP_URL', 'http://localhost')),
        'support_email' => env('MAIL_BRAND_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
        'logo_url' => env('MAIL_BRAND_LOGO_URL'),
        'primary_color' => env('MAIL_BRAND_PRIMARY_COLOR', '#1a9e99'),
        'accent_color' => env('MAIL_BRAND_ACCENT_COLOR', '#d4891f'),
    ],

    'subjects' => [
        'otp' => env('MAIL_SUBJECT_OTP', 'Your OTP Code'),
        'password_reset' => env('MAIL_SUBJECT_PASSWORD_RESET', 'Password Reset OTP'),
        'contact_admin' => env('MAIL_SUBJECT_CONTACT_ADMIN', 'New Contact Inquiry'),
        'contact_user' => env('MAIL_SUBJECT_CONTACT_USER', 'We Received Your Message'),
        'appointment_user' => env('MAIL_SUBJECT_APPOINTMENT_USER', 'Your Appointment Is Confirmed'),
        'appointment_lawyer' => env('MAIL_SUBJECT_APPOINTMENT_LAWYER', 'New Appointment Assigned'),
    ],
];
