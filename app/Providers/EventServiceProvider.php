<?php

namespace App\Providers;

use App\Events\AppointmentBooked;
use App\Listeners\SendAppointmentWhatsAppNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AppointmentBooked::class => [
            SendAppointmentWhatsAppNotification::class,
        ],
    ];
}

