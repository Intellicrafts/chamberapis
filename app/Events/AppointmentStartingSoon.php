<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when approximately 1 minute remains before an appointment starts.
 * Triggered by the appointments:send-reminders artisan command.
 *
 * NOTE: Does NOT use ShouldDispatchAfterCommit because it's fired from
 * a CLI command (no active DB transaction), not from a controller.
 */
class AppointmentStartingSoon
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
    ) {}
}
