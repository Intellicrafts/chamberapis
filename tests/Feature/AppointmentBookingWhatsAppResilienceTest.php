<?php

namespace Tests\Feature;

use App\Events\AppointmentBooked;
use App\Http\Controllers\API\AppointmentController;
use App\Services\Mail\AppMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Mockery;
use Tests\TestCase;

class AppointmentBookingWhatsAppResilienceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lawyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('password_hash')->default('hash');
            $table->string('enrollment_no')->default('ENR-001');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('lawyer_id');
            $table->timestamp('appointment_time');
            $table->integer('duration_minutes');
            $table->string('status');
            $table->text('meeting_link')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Client One',
            'email' => 'client@example.com',
            'password' => bcrypt('secret'),
            'phone' => '+919876543210',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lawyers')->insert([
            'id' => 1,
            'user_id' => null,
            'full_name' => 'Lawyer One',
            'email' => 'lawyer@example.com',
            'phone_number' => '+919111111111',
            'password_hash' => 'hash',
            'enrollment_no' => 'ENR-002',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('lawyers');
        Schema::dropIfExists('users');

        Mockery::close();
        parent::tearDown();
    }

    public function test_appointment_booking_succeeds_even_if_event_dispatch_fails(): void
    {
        Event::listen(AppointmentBooked::class, function () {
            throw new \RuntimeException('Event dispatch failed');
        });

        $mailService = Mockery::mock(AppMailService::class);
        $mailService->shouldReceive('sendAppointmentBookedNotifications')->once();

        $request = Request::create('/api/appointments', 'POST', [
            'user_id' => 1,
            'lawyer_id' => 1,
            'appointment_time' => now()->addDay()->toDateTimeString(),
            'duration_minutes' => 30,
        ]);

        $response = app(AppointmentController::class)->store($request, $mailService);

        $this->assertSame(201, $response->status(), $response->getContent());
        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertDatabaseCount('appointments', 1);
    }
}
