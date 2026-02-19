<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Lawyer;
use App\Models\ComplianceEvent;

class ComplianceEventSeeder extends Seeder
{
    /**
     * Seed 20 rows into `compliance_events`.
     *
     * Covers all three event types in a realistic distribution:
     *   - minor_complaint     (most common, ~50 %)
     *   - refund_dispute_loss (~30 %)
     *   - verified_misconduct (least common, ~20 %)
     *
     * ~60 % of events are already resolved; the rest are still open.
     */
    public function run(): void
    {
        $lawyers = Lawyer::orderBy('id')->get();

        if ($lawyers->isEmpty()) {
            $this->command->warn('[ComplianceEventSeeder] No lawyers found – skipping.');
            return;
        }

        $events = [
            // ── minor_complaint (10 rows) ────────────────────────────────────
            ['type' => ComplianceEvent::TYPE_MINOR_COMPLAINT,     'desc' => 'Client reported delayed response to messages.',            'days_ago' => 10,  'resolved' => true],
            ['type' => ComplianceEvent::TYPE_MINOR_COMPLAINT,     'desc' => 'User complained about unclear fee structure.',             'days_ago' => 20,  'resolved' => true],
            ['type' => ComplianceEvent::TYPE_MINOR_COMPLAINT,     'desc' => 'Appointment rescheduled without prior notice.',            'days_ago' => 35,  'resolved' => false],
            ['type' => ComplianceEvent::TYPE_MINOR_COMPLAINT,     'desc' => 'Late response to submitted legal documents.',              'days_ago' => 45,  'resolved' => true],
            ['type' => ComplianceEvent::TYPE_MINOR_COMPLAINT,     'desc' => 'Client felt advice was too generic.',                     'days_ago' => 60,  'resolved' => false],
            ['type' => ComplianceEvent::TYPE_MINOR_COMPLAINT,     'desc' => 'Meeting link sent 5 minutes after scheduled start.',      'days_ago' => 75,  'resolved' => true],
            ['type' => ComplianceEvent::TYPE_MINOR_COMPLAINT,     'desc' => 'Video call quality issue attributed to lawyer setup.',    'days_ago' => 90,  'resolved' => true],
            ['type' => ComplianceEvent::TYPE_MINOR_COMPLAINT,     'desc' => 'Billing discrepancy reported by client.',                 'days_ago' => 105, 'resolved' => false],
            ['type' => ComplianceEvent::TYPE_MINOR_COMPLAINT,     'desc' => 'Client reported missed follow-up commitment.',            'days_ago' => 120, 'resolved' => true],
            ['type' => ComplianceEvent::TYPE_MINOR_COMPLAINT,     'desc' => 'Legal advice contradicted a prior written opinion.',      'days_ago' => 135, 'resolved' => false],
            // ── refund_dispute_loss (6 rows) ─────────────────────────────────
            ['type' => ComplianceEvent::TYPE_REFUND_DISPUTE_LOSS, 'desc' => 'Refund granted after arbitration – case closed.',        'days_ago' => 15,  'resolved' => true],
            ['type' => ComplianceEvent::TYPE_REFUND_DISPUTE_LOSS, 'desc' => 'Client dispute panel ruled in favor of user.',           'days_ago' => 40,  'resolved' => true],
            ['type' => ComplianceEvent::TYPE_REFUND_DISPUTE_LOSS, 'desc' => 'Partial refund ordered due to incomplete consultation.',  'days_ago' => 70,  'resolved' => true],
            ['type' => ComplianceEvent::TYPE_REFUND_DISPUTE_LOSS, 'desc' => 'Refund dispute under review.',                           'days_ago' => 5,   'resolved' => false],
            ['type' => ComplianceEvent::TYPE_REFUND_DISPUTE_LOSS, 'desc' => 'User refund approved after missed appointment.',         'days_ago' => 110, 'resolved' => true],
            ['type' => ComplianceEvent::TYPE_REFUND_DISPUTE_LOSS, 'desc' => 'Double-billing dispute resolved in client\'s favor.',    'days_ago' => 150, 'resolved' => true],
            // ── verified_misconduct (4 rows) ──────────────────────────────────
            ['type' => ComplianceEvent::TYPE_VERIFIED_MISCONDUCT, 'desc' => 'Lawyer shared client confidential info without consent.','days_ago' => 30,  'resolved' => false],
            ['type' => ComplianceEvent::TYPE_VERIFIED_MISCONDUCT, 'desc' => 'Conflict of interest not disclosed before consultation.',  'days_ago' => 80,  'resolved' => true],
            ['type' => ComplianceEvent::TYPE_VERIFIED_MISCONDUCT, 'desc' => 'Provided legal advice outside licensed jurisdiction.',    'days_ago' => 100, 'resolved' => false],
            ['type' => ComplianceEvent::TYPE_VERIFIED_MISCONDUCT, 'desc' => 'Misrepresented case outcome to client.',                 'days_ago' => 200, 'resolved' => true],
        ];

        $inserted = 0;
        foreach ($events as $idx => $evt) {
            $lawyer     = $lawyers[$idx % $lawyers->count()];
            $occurredAt = Carbon::now()->subDays($evt['days_ago']);
            $resolvedAt = $evt['resolved'] ? $occurredAt->copy()->addDays(rand(2, 14)) : null;

            ComplianceEvent::create([
                'lawyer_id'   => $lawyer->id,
                'event_type'  => $evt['type'],
                'description' => $evt['desc'],
                'occurred_at' => $occurredAt,
                'created_at'  => $occurredAt->copy()->addHours(rand(1, 48)),
                'resolved_at' => $resolvedAt,
            ]);

            $inserted++;
            $this->command->line("  ✔ ComplianceEvent #{$inserted}: lawyer_id={$lawyer->id}, type={$evt['type']}");
        }

        $this->command->info("[ComplianceEventSeeder] Done – {$inserted} rows inserted.");
    }
}
