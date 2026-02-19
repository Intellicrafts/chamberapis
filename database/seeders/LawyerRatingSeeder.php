<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Lawyer;
use App\Models\LawyerRating;

class LawyerRatingSeeder extends Seeder
{
    /**
     * Seed 20 rows into `lawyers_rating`.
     *
     * Strategy:
     *   - Use existing lawyers from the DB.
     *   - If fewer than 20 lawyers exist, cycle through them.
     *   - Each row gets a realistic rating_score → tier derived from that score.
     */
    public function run(): void
    {
        $lawyers = Lawyer::orderBy('id')->get();

        if ($lawyers->isEmpty()) {
            $this->command->warn('[LawyerRatingSeeder] No lawyers found – skipping.');
            return;
        }

        // Sample rating data (20 varied entries)
        $samples = [
            ['score' => 92.50, 'reviews' => 145, 'interactions' => 312],
            ['score' => 88.00, 'reviews' => 110, 'interactions' => 245],
            ['score' => 75.25, 'reviews' =>  89, 'interactions' => 178],
            ['score' => 71.00, 'reviews' =>  67, 'interactions' => 134],
            ['score' => 68.50, 'reviews' =>  55, 'interactions' => 102],
            ['score' => 60.00, 'reviews' =>  42, 'interactions' =>  88],
            ['score' => 55.75, 'reviews' =>  38, 'interactions' =>  74],
            ['score' => 50.00, 'reviews' =>  30, 'interactions' =>  60],
            ['score' => 45.25, 'reviews' =>  24, 'interactions' =>  48],
            ['score' => 40.00, 'reviews' =>  18, 'interactions' =>  36],
            ['score' => 35.50, 'reviews' =>  14, 'interactions' =>  28],
            ['score' => 31.00, 'reviews' =>  11, 'interactions' =>  22],
            ['score' => 28.75, 'reviews' =>   8, 'interactions' =>  16],
            ['score' => 22.00, 'reviews' =>   5, 'interactions' =>  10],
            ['score' => 18.50, 'reviews' =>   4, 'interactions' =>   8],
            ['score' => 15.00, 'reviews' =>   3, 'interactions' =>   6],
            ['score' =>  9.25, 'reviews' =>   2, 'interactions' =>   4],
            ['score' =>  5.00, 'reviews' =>   1, 'interactions' =>   2],
            ['score' =>  2.50, 'reviews' =>   1, 'interactions' =>   1],
            ['score' =>  0.00, 'reviews' =>   0, 'interactions' =>   0],
        ];

        $inserted = 0;
        foreach ($samples as $idx => $sample) {
            // Cycle through available lawyers
            $lawyer = $lawyers[$idx % $lawyers->count()];

            // Skip if a rating already exists for this lawyer (idempotent)
            if (LawyerRating::where('lawyer_id', $lawyer->id)->exists()) {
                $this->command->line("  – Lawyer #{$lawyer->id} already has a rating row – skipping.");
                continue;
            }

            $tier = LawyerRating::tierFromScore($sample['score']);

            LawyerRating::create([
                'lawyer_id'          => $lawyer->id,
                'rating_score'       => $sample['score'],
                'tier'               => $tier,
                'total_reviews'      => $sample['reviews'],
                'total_interactions' => $sample['interactions'],
            ]);

            $inserted++;
            $this->command->line("  ✔ LawyerRating #{$inserted}: lawyer_id={$lawyer->id}, score={$sample['score']}, tier={$tier}");
        }

        $this->command->info("[LawyerRatingSeeder] Done – {$inserted} rows inserted.");
    }
}
