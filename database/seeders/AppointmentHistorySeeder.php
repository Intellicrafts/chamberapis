<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Appointment;
use App\Models\AppointmentHistory;
use App\Models\Lawyer;
use App\Models\User;

class AppointmentHistorySeeder extends Seeder
{
    /**
     * Seed 20 rows into `appointments_history`.
     *
     * Strategy:
     *   - Pull existing appointments; if none exist, create synthetic rows
     *     using real user_id / lawyer_id pairs so FKs remain satisfied.
     *   - Covers all outcome statuses and a mix of paid/free appointments.
     */
    public function run(): void
    {
        $appointments = Appointment::with(['user'])->orderBy('id')->get();
        $users        = User::orderBy('id')->pluck('id')->toArray();
        $lawyers      = Lawyer::orderBy('id')->pluck('id')->toArray();

        if (empty($users) || empty($lawyers)) {
            $this->command->warn('[AppointmentHistorySeeder] No users or lawyers found – skipping.');
            return;
        }

        $statuses = [
            AppointmentHistory::STATUS_COMPLETED,
            AppointmentHistory::STATUS_COMPLETED,
            AppointmentHistory::STATUS_COMPLETED,   // weight towards completed
            AppointmentHistory::STATUS_NO_SHOW,
            AppointmentHistory::STATUS_LATE,
            AppointmentHistory::STATUS_CANCELLED,
            AppointmentHistory::STATUS_RESCHEDULED,
        ];

        $cancellationReasons = [
            'User requested cancellation',
            'Lawyer cancelled due to emergency',
            'Client no-show',
            'Rescheduled by mutual agreement',
            'Technical issues on lawyer side',
            null,   // no reason given
        ];

        $inserted = 0;

        for ($i = 0; $i < 20; $i++) {
            // Use real appointment if available, else pick any combination
            $appt   = $appointments->count() ? $appointments[$i % $appointments->count()] : null;
            $apptId = $appt ? $appt->id : null;
            $userId   = $appt ? $appt->user_id   : $users[$i % count($users)];
            $lawyerId = $appt ? $appt->lawyer_id  : $lawyers[$i % count($lawyers)];

            // If no appointment row available, skip appointment_id (nullable not allowed by FK)
            // Instead associate with the first real appointment that exists
            if (! $apptId && $appointments->isEmpty()) {
                $this->command->warn('  ⚠ No appointments in DB. AppointmentHistory row cannot be created without a valid appointment_id.');
                break;
            }

            $status = $statuses[$i % count($statuses)];
            $base   = Carbon::now()->subDays(rand(1, 120))->setHour(rand(9, 17))->setMinute(0)->setSecond(0);

            // Determine join timestamps based on status
            [$lawyerJoined, $userJoined] = match ($status) {
                AppointmentHistory::STATUS_COMPLETED   => [$base->copy()->addMinutes(rand(0, 3)), $base->copy()->addMinutes(rand(0, 5))],
                AppointmentHistory::STATUS_LATE        => [$base->copy()->addMinutes(rand(7, 20)), $base->copy()->addMinutes(rand(0, 2))],
                AppointmentHistory::STATUS_NO_SHOW     => [null, $base->copy()->addMinutes(rand(0, 2))],
                AppointmentHistory::STATUS_CANCELLED   => [null, null],
                AppointmentHistory::STATUS_RESCHEDULED => [null, null],
                default                                => [null, null],
            };

            $isPaid             = ($i % 3 !== 0);   // roughly 2/3 paid
            $cancellationReason = in_array($status, [AppointmentHistory::STATUS_CANCELLED, AppointmentHistory::STATUS_RESCHEDULED])
                ? $cancellationReasons[$i % count($cancellationReasons)]
                : null;

            AppointmentHistory::create([
                'appointment_id'      => $apptId,
                'user_id'             => $userId,
                'lawyer_id'           => $lawyerId,
                'status'              => $status,
                'lawyer_joined_at'    => $lawyerJoined,
                'user_joined_at'      => $userJoined,
                'is_paid'             => $isPaid,
                'cancellation_reason' => $cancellationReason,
                'appointment_data'    => [
                    'appointment_time'  => $base->toIso8601String(),
                    'duration_minutes'  => 30,
                    'meeting_link'      => 'https://meet.meravakil.com/room-' . strtolower(substr(md5($i), 0, 8)),
                ],
            ]);

            $inserted++;
            $this->command->line("  ✔ AppointmentHistory #{$inserted}: appt_id={$apptId}, lawyer_id={$lawyerId}, status={$status}");
        }

        $this->command->info("[AppointmentHistorySeeder] Done – {$inserted} rows inserted.");
    }
}
