<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LawyerCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * NOTE: lawyer_categories.id is bigIncrements (auto-increment integer).
     * Do NOT pass a UUID as `id` — MySQL will truncate it and throw SQLSTATE[01000].
     * We let the DB auto-assign the integer ID.
     */
    public function run(): void
    {
        $categories = [
            'Criminal Law',
            'Family Law',
            'Corporate Law',
            'Intellectual Property',
            'Real Estate Law',
            'Tax Law',
            'Immigration Law',
            'Labor Law',
            'Environmental Law',
            'Constitutional Law',
            'Civil Rights Law',
            'Personal Injury',
            'Medical Malpractice',
            'Bankruptcy',
            'Estate Planning',
        ];

        foreach ($categories as $category) {
            // Idempotent: skip if already exists
            if (DB::table('lawyer_categories')->where('category_name', $category)->exists()) {
                $this->command->line("  – Category '{$category}' already exists – skipping.");
                continue;
            }

            DB::table('lawyer_categories')->insert([
                // No 'id' key — let MySQL auto-assign the bigIncrements value
                'category_name' => $category,
                'lawyer_id'     => null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $this->command->line("  ✔ Category created: {$category}");
        }

        $this->command->info('[LawyerCategorySeeder] Done.');
    }
}
