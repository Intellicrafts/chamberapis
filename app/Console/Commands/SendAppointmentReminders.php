<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Events\AppointmentStartingSoon;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders
                            {--minutes=1  : Send reminders for appointments this many minutes ahead}
                            {--window=2   : Window (±minutes) to catch appointments around the target time}';

    protected $description = 'Send WhatsApp 1-minute reminders for upcoming appointments (run every minute via scheduler).';

    public function handle(WhatsAppService $whatsAppService): int
    {
        $minutesAhead = (int) $this->option('minutes');
        $window       = (int) $this->option('window');

        $from = now()->addMinutes($minutesAhead - $window);
        $to   = now()->addMinutes($minutesAhead + $window);

        $this->info("Looking for appointments between {$from->format('H:i')} and {$to->format('H:i')} ...");

        $appointments = Appointment::with(['user', 'lawyer', 'lawyer.user'])
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->whereBetween('appointment_time', [$from, $to])
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No appointments found in this window. Nothing to send.');
            return self::SUCCESS;
        }

        $sentCount    = 0;
        $skippedCount = 0;

        foreach ($appointments as $appointment) {
            $this->line("Processing appointment #{$appointment->id} at {$appointment->appointment_time->format('d M Y h:i A')}");

            // Check whether reminder was already sent for this appointment
            $alreadySent = \App\Models\WhatsAppLog::where('appointment_id', $appointment->id)
                ->whereIn('message_type', ['reminder_client', 'reminder_lawyer'])
                ->exists();

            if ($alreadySent) {
                $this->warn("  -> Reminder already sent for appointment #{$appointment->id}, skipping.");
                $skippedCount++;
                continue;
            }

            try {
                // Fire event — auto-discovered listener sends the WhatsApp
                event(new AppointmentStartingSoon($appointment));
                $sentCount++;
                $this->info("  OK Reminder fired for appointment #{$appointment->id}");

                Log::info('Appointment reminder event fired.', [
                    'appointment_id'   => $appointment->id,
                    'appointment_time' => $appointment->appointment_time->toDateTimeString(),
                ]);
            } catch (\Throwable $e) {
                $skippedCount++;
                $this->error("  FAIL for appointment #{$appointment->id}: " . $e->getMessage());
                Log::error('Failed to fire appointment reminder event.', [
                    'appointment_id' => $appointment->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Sent: {$sentCount}, Skipped/Failed: {$skippedCount}.");

        return self::SUCCESS;
    }
}
