<?php

namespace Tests\Unit;

use App\Events\AppointmentBooked;
use App\Jobs\SendWhatsAppMessage;
use App\Listeners\SendAppointmentWhatsAppNotification;
use App\Models\Appointment;
use App\Models\Lawyer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendAppointmentWhatsAppNotificationTest extends TestCase
{
    public function test_it_dispatches_whatsapp_job_for_client_and_lawyer(): void
    {
        Queue::fake();

        $appointment = new Appointment([
            'appointment_time' => Carbon::parse('2026-03-15 10:30:00'),
        ]);
        $appointment->id = 123;

        $client = new User([
            'name' => 'Rahul',
            'phone' => '919876543210',
        ]);
        $client->id = 10;

        $lawyer = new Lawyer([
            'full_name' => 'Adv. Meera',
            'phone_number' => '+919999999999',
        ]);
        $lawyer->id = 20;

        $event = new AppointmentBooked($appointment, $client, $lawyer);
        $listener = new SendAppointmentWhatsAppNotification();
        $listener->handle($event);

        Queue::assertPushed(SendWhatsAppMessage::class, 2);

        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->messageType === 'appointment_confirmation_client'
                && $job->phone === '919876543210'
                && $job->variables['1'] === '2026-03-15'
                && $job->variables['2'] === '10:30 AM';
        });

        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->messageType === 'appointment_notification_lawyer'
                && $job->phone === '+919999999999'
                && $job->variables['1'] === 'Rahul'
                && $job->variables['2'] === '10:30 AM';
        });
    }
}

