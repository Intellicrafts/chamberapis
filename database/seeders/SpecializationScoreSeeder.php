<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Lawyer;
use App\Models\SpecializationScore;

class SpecializationScoreSeeder extends Seeder
{
    /**
     * Seed 20 rows into `specialization_scores`.
     *
     * Uses a realistic set of practice areas and distributes them across
     * available lawyers so that each lawyer can have multiple
     * specialization entries (up to the UNIQUE constraint).
     */
    public function run(): void
    {
        $lawyers = Lawyer::orderBy('id')->get();

        if ($lawyers->isEmpty()) {
            $this->command->warn('[SpecializationScoreSeeder] No lawyers found – skipping.');
            return;
        }

        $specializationPool = [
            'Criminal Law',
            'Family Law',
            'Civil Law',
            'Corporate Law',
            'Property Law',
            'Intellectual Property',
            'Labour & Employment',
            'Consumer Protection',
            'Taxation Law',
            'Constitutional Law',
        ];

        // Build 20 unique (lawyer, specialization) pairs
        $pairs   = [];
        $attempt = 0;

        while (count($pairs) < 20 && $attempt < 200) {
            $attempt++;
            $lawyer = $lawyers->random();
            $spec   = $specializationPool[array_rand($specializationPool)];
            $key    = "{$lawyer->id}|{$spec}";

            if (isset($pairs[$key])) {
                continue;  // already queued
            }

            // Also skip if row already exists in DB (idempotent runs)
            if (SpecializationScore::where('lawyer_id', $lawyer->id)->where('specialization', $spec)->exists()) {
                $pairs[$key] = null; // mark consumed so we don't keep trying
                continue;
            }

            $pairs[$key] = [
                'lawyer_id'      => $lawyer->id,
                'specialization' => $spec,
                'score'          => round(rand(2500, 9800) / 100, 2), // 25.00 – 98.00
                'last_updated'   => Carbon::now()->subDays(rand(0, 60)),
            ];
        }

        $inserted = 0;
        foreach (array_filter($pairs) as $data) {
            SpecializationScore::create($data);
            $inserted++;
            $this->command->line("  ✔ SpecializationScore #{$inserted}: lawyer_id={$data['lawyer_id']}, spec={$data['specialization']}, score={$data['score']}");
        }

        $this->command->info("[SpecializationScoreSeeder] Done – {$inserted} rows inserted.");
    }
}
