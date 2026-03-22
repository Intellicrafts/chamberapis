<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class LawyerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('en_IN');
        $this->command->info('Creating 20 Lawyers from existing users...');

        // Get 20 active users
        $users = DB::table('users')->where('active', 1)->take(20)->get();

        if ($users->count() < 20) {
            $this->command->warn('Less than 20 users available. Seeding for ' . $users->count() . ' users.');
        }

        $categories = [
            'Criminal',
            'Family',
            'Corporate',
            'Immigration',
            'Civil',
            'Labor Law',
            'Tax Law',
            'Intellectual Property'
        ];

        foreach ($users as $user) {
            // Update user to be a lawyer type
            DB::table('users')->where('id', $user->id)->update(['user_type' => 2]);

            // Pick a specialization category
            $categoryName = $faker->randomElement($categories);

            // Create or Update Lawyer profile
            DB::table('lawyers')->updateOrInsert(
                ['email' => $user->email],
                [
                    'user_id'            => $user->id,
                    'full_name'          => $user->name,
                    'phone_number'       => $user->phone ?? '9' . $faker->randomNumber(8, true),
                    'password_hash'      => $user->password,
                    'active'             => 1,
                    'is_verified'        => 1,
                    'status'             => 'active',
                    'enrollment_no'      => strtoupper($faker->bothify('??####??###')),
                    'bar_association'    => 'Bar Council of India',
                    'specialization'     => $categoryName, // Also store in lawyers table as fallback/info
                    'years_of_experience'=> $faker->numberBetween(1, 30),
                    'bio'                => $faker->paragraph(),
                    'consultation_fee'   => $faker->numberBetween(500, 5000),
                    'deleted'            => 0,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]
            );

            // Fetch the updated/inserted lawyer ID
            $lawyerId = DB::table('lawyers')->where('email', $user->email)->value('id');

            // Add Lawyer Category linking
            DB::table('lawyer_categories')->updateOrInsert(
                ['category_name' => $categoryName],
                [
                    'lawyer_id'  => $lawyerId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
            
            $this->command->line("  ✔ Lawyer populated & categorized: {$user->name} as {$categoryName}");
        }

        $this->command->info('[LawyerSeeder] Done converting users to Lawyers and mapping categories.');
    }
}
