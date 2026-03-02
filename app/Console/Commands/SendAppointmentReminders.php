<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders
                            {--minutes=60 : Send reminders for appointments this many minutes ahead}
                            {--window=5   : Window (±minutes) to catch appointments around the target time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp reminders for upcoming appointments (run every 5 min via scheduler).';

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
            Log::info('appointments:send-reminders — no appointments in window.', [
                'from'   => $from->toDateTimeString(),
                'to'     => $to->toDateTimeString(),
            ]);
            return self::SUCCESS;
        }

        $sentCount    = 0;
        $skippedCount = 0;

        foreach ($appointments as $appointment) {
            $this->line("Processing appointment #{$appointment->id} at {$appointment->appointment_time->format('d M Y h:i A')}");

            // Check whether reminder was already sent for this appointment
            $alreadySent = \App\Models\WhatsAppLog::where('appointment_id', $appointment->id)
                ->whereIn('message_type', ['appointment_reminder_client', 'appointment_reminder_lawyer'])
                ->exists();

            if ($alreadySent) {
                $this->warn("  → Reminder already sent for appointment #{$appointment->id}, skipping.");
                $skippedCount++;
                continue;
            }

            try {
                $whatsAppService->sendReminderNotification($appointment);
                $sentCount++;
                $this->info("  ✓ Reminder sent for appointment #{$appointment->id}");

                Log::info('Reminder sent for appointment.', [
                    'appointment_id' => $appointment->id,
                    'appointment_time' => $appointment->appointment_time->toDateTimeString(),
                ]);
            } catch (\Throwable $e) {
                $skippedCount++;
                $this->error("  ✗ Failed to send reminder for appointment #{$appointment->id}: " . $e->getMessage());
                Log::error('Failed to send appointment reminder.', [
                    'appointment_id' => $appointment->id,
                    'error'          => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Sent: {$sentCount}, Skipped/Failed: {$skippedCount}.");

        return self::SUCCESS;
    }
}
