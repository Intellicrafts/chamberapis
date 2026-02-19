<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Designed to work correctly with BOTH:
     *   php artisan migrate:fresh --seed       (drops all, rebuilds from scratch)
     *   php artisan db:seed --class=XxxSeeder  (individual seeder runs)
     *
     * Run order is critical — each group depends on the previous one.
     */
    public function run(): void
    {
        // ── 1. Lookup / reference data (no foreign keys) ────────────────────
        $this->call([
            LawyerCategorySeeder::class,   // fixed: no longer injects UUID as id
        ]);

        // ── 2. Core entities: users, lawyers, appointments ───────────────────
        // BaseDataSeeder is idempotent — skips rows that already exist
        $this->call([
            BaseDataSeeder::class,
        ]);

        // ── 3. Lawyer Rating & Reputation System ────────────────────────────
        // Depends on: lawyers (step 2), appointments (step 2), users (step 2)
        $this->call([
            LawyerRatingSeeder::class,        // lawyers_rating  (20 rows)
            AppointmentHistorySeeder::class,  // appointments_history (20 rows)
            ReviewAntiGamingSeeder::class,    // reviews incl. ip/device_id (20 rows)
            ComplianceEventSeeder::class,     // compliance_events (20 rows)
            SpecializationScoreSeeder::class, // specialization_scores (20 rows)
        ]);
    }
}
