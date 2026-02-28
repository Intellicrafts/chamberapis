<?php

namespace App\Events;

use App\Models\Appointment;
use App\Models\Lawyer;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentBooked implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public User $client,
        public Lawyer $lawyer,
    ) {
    }
}

