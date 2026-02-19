<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run order matters – new Rating & Reputation seeders depend on
     * lawyers, users, and appointments already existing.
     */
    public function run(): void
    {
        // ── Existing base seeders (keep original data) ───────────────────────
        $this->call([
            // These were previously run individually; keeping them here
            // makes a fresh `php artisan db:seed` fully reproducible.
            LawyerCategorySeeder::class,
            // AppointmentSeeder::class,     // uncomment if needed
            // AvailabilitySlotSeeder::class, // uncomment if needed
            // ReviewSeeder::class,           // uncomment if needed
        ]);

        // ── Lawyer Rating & Reputation System seeders ────────────────────────
        // Order: LawyerRating → AppointmentHistory → Reviews (anti-gaming)
        //        → ComplianceEvents → SpecializationScores
        $this->call([
            LawyerRatingSeeder::class,
            AppointmentHistorySeeder::class,
            ReviewAntiGamingSeeder::class,
            ComplianceEventSeeder::class,
            SpecializationScoreSeeder::class,
        ]);
    }
}

