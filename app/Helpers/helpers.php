<?php

// ============================================================
// Global Helper Functions
// ============================================================

if (!function_exists('send_email')) {
    /**
     * Send an email globally using Laravel's Mail facade.
     *
     * @param  string $to
     * @param  string $subject
     * @param  string $view
     * @param  array  $data
     * @return void
     */
    function send_email(string $to, string $subject, string $view, array $data = []): void
    {
        \Illuminate\Support\Facades\Mail::send($view, $data, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }
}
