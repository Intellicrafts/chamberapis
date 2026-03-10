<?php

namespace App\Services\Mail;

use App\Mail\GenericMail;
use Illuminate\Support\Facades\Mail;

class AppMailService
{
    public function send(
        string|array $to,
        string $subject,
        string $view,
        array $data = [],
        ?array $replyTo = null
    ): void {
        Mail::to($to)->send(
            new GenericMail(
                subjectLine: $subject,
                viewName: $view,
                viewData: $this->withBrandData($data),
                replyToAddress: $replyTo
            )
        );
    }

    public function sendOtp(string $email, string $otp, string $title = 'Verification OTP'): void
    {
        $this->send(
            to: $email,
            subject: config('mailing.subjects.otp', 'Your OTP Code'),
            view: 'emails.templates.otp',
            data: [
                'title' => $title,
                'otp' => $otp,
                'expiresInMinutes' => 10,
            ]
        );
    }

    public function sendPasswordResetOtp(string $email, string $otp): void
    {
        $this->send(
            to: $email,
            subject: config('mailing.subjects.password_reset', 'Password Reset OTP'),
            view: 'emails.templates.otp',
            data: [
                'title' => 'Password Reset OTP',
                'otp' => $otp,
                'expiresInMinutes' => 10,
            ]
        );
    }

    public function sendContactNotifications(array $contact): void
    {
        $adminRecipients = config('mailing.admin_recipients', []);
        if (empty($adminRecipients)) {
            $adminRecipients = [config('mail.from.address')];
        }

        $this->send(
            to: $adminRecipients,
            subject: config('mailing.subjects.contact_admin', 'New Contact Inquiry'),
            view: 'emails.templates.contact-admin',
            data: ['contact' => $contact],
            replyTo: [
                'address' => $contact['email_address'],
                'name' => $contact['full_name'],
            ]
        );

        $this->send(
            to: $contact['email_address'],
            subject: config('mailing.subjects.contact_user', 'We Received Your Message'),
            view: 'emails.templates.contact-user',
            data: ['contact' => $contact]
        );
    }


    public function sendWelcomeClientEmail(string $email, string $userName, string $joinedAt): void
    {
        $this->send(
            to: $email,
            subject: 'Welcome to MeraBakil — Your Legal Journey Starts Now! 🎉',
            view: 'emails.templates.welcome-client',
            data: [
                'userName'  => $userName,
                'userEmail' => $email,
                'joinedAt'  => $joinedAt,
            ]
        );
    }

    public function sendWelcomeLawyerEmail(
        string $email,
        string $lawyerName,
        string $enrollmentNo,
        string $specialization,
        string $joinedAt
    ): void {
        $this->send(
            to: $email,
            subject: 'Welcome to MeraBakil Advocate Portal 🏛️',
            view: 'emails.templates.welcome-lawyer',
            data: [
                'lawyerName'     => $lawyerName,
                'lawyerEmail'    => $email,
                'enrollmentNo'   => $enrollmentNo,
                'specialization' => $specialization,
                'joinedAt'       => $joinedAt,
            ]
        );
    }

    public function sendAppointmentReminderEmails(array $appointment): void
    {
        $clientEmail = $appointment['user_email'] ?? null;
        if (!empty($clientEmail)) {
            $this->send(
                to: $clientEmail,
                subject: '⏰ Your Session Starts in 5 Minutes — MeraBakil',
                view: 'emails.templates.appointment-reminder',
                data: ['appointment' => $appointment]
            );
        }

        $lawyerEmail = $appointment['lawyer_official_email'] ?? $appointment['lawyer_email'] ?? null;
        if (!empty($lawyerEmail)) {
            $this->send(
                to: $lawyerEmail,
                subject: '⏰ Consultation Begins in 5 Minutes — MeraBakil',
                view: 'emails.templates.appointment-reminder',
                data: ['appointment' => $appointment]
            );
        }
    }

    private function withBrandData(array $data): array
    {
        return array_merge([
            'brand' => config('mailing.brand', []),
            'year' => date('Y'),
        ], $data);
    }
}
