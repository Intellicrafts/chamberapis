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

    public function sendAppointmentBookedNotifications(array $appointment): void
    {
        $appointmentDateTime = $appointment['appointment_time'] ?? null;
        $timezone = config('app.timezone', 'UTC');

        if (!empty($appointmentDateTime)) {
            try {
                $dt = \Carbon\Carbon::parse($appointmentDateTime)->timezone($timezone);
                $appointment['appointment_date'] = $dt->format('l, d M Y');
                $appointment['appointment_time_formatted'] = $dt->format('h:i A');
            } catch (\Throwable) {
                $appointment['appointment_date'] = $appointmentDateTime;
                $appointment['appointment_time_formatted'] = $appointmentDateTime;
            }
        }

        $appointment['timezone'] = $timezone;

        $userEmail = $appointment['user_email'] ?? null;
        if (!empty($userEmail)) {
            $this->send(
                to: $userEmail,
                subject: config('mailing.subjects.appointment_user', 'Your Appointment Is Confirmed'),
                view: 'emails.templates.appointment-booked-user',
                data: ['appointment' => $appointment]
            );
        }

        $lawyerEmail = $appointment['lawyer_official_email'] ?? $appointment['lawyer_email'] ?? null;
        if (!empty($lawyerEmail)) {
            $this->send(
                to: $lawyerEmail,
                subject: config('mailing.subjects.appointment_lawyer', 'New Appointment Assigned'),
                view: 'emails.templates.appointment-booked-lawyer',
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
