<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\Mail\AppMailService;
use Illuminate\Support\Facades\Log;

class SendWelcomeEmailNotification
{
    public function handle(UserRegistered $event): void
    {
        try {
            $user   = $event->user;
            $lawyer = $event->lawyer;

            $mailService = app(AppMailService::class);

            if ($lawyer) {
                // ── Lawyer / Business registration ──────────────────────
                $lawyer->loadMissing('user');

                $mailService->send(
                    to: $user->email,
                    subject: 'Welcome to MeraBakil Advocate Portal 🏛️',
                    view: 'emails.templates.welcome-lawyer',
                    data: [
                        'lawyerName'    => $lawyer->full_name ?? $user->name,
                        'lawyerEmail'   => $user->email,
                        'enrollmentNo'  => $lawyer->enrollment_no ?? 'Pending',
                        'specialization'=> $lawyer->specialization ?? 'General',
                        'joinedAt'      => $user->created_at?->format('d M Y') ?? now()->format('d M Y'),
                    ]
                );

            } else {
                // ── Regular client registration ──────────────────────────
                $mailService->send(
                    to: $user->email,
                    subject: 'Welcome to MeraBakil — Your Legal Journey Starts Now! 🎉',
                    view: 'emails.templates.welcome-client',
                    data: [
                        'userName'  => $user->name,
                        'userEmail' => $user->email,
                        'joinedAt'  => $user->created_at?->format('d M Y') ?? now()->format('d M Y'),
                    ]
                );
            }

        } catch (\Throwable $e) {
            Log::error('SendWelcomeEmailNotification failed.', [
                'user_id' => $event->user?->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
