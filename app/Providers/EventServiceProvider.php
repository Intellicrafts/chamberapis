<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     * Note: Laravel auto-discovers events in the app/Listeners directory.
     * Defining them here manually causes double-firing of events.
     *
     * @var array
     */
    protected $listen = [
        \App\Events\AppointmentBooked::class => [
            \App\Listeners\SendAppointmentWhatsAppNotification::class,
        ],
        \App\Events\UserJoinedConsultation::class => [
            \App\Listeners\SendJoinAlertWhatsAppNotification::class,
        ],
        \App\Events\ConsultationSessionEnded::class => [
            \App\Listeners\SendSessionEndedWhatsAppNotification::class,
        ],
    ];
}
