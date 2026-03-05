<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when approximately 1 minute remains before an appointment starts.
 * Triggered by the appointments:send-reminders artisan command.
 */
class AppointmentStartingSoon implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
    ) {}
}
