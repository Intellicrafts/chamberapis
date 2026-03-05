<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * LEFT EMPTY intentionally — Laravel auto-discovers all listeners in
     * app/Listeners/ via the handle(EventClass $event) type-hint.
     * Registering them here AS WELL causes every event to fire twice.
     *
     * @var array
     */
    protected $listen = [];
}
