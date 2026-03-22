<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('en_IN');
        $this->command->info('Creating 50 Users...');

        $avatars = [
            'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80',
            'https://t4.ftcdn.net/jpg/03/46/93/61/360_F_346936114_RaxE6OQogOtOt9Wgc91G1oST5p5huzJS.jpg'
        ];

        for ($i = 0; $i < 50; $i++) {
            $email = $faker->unique()->safeEmail();
            
            DB::table('users')->insert([
                'name'              => $faker->name(),
                'email'             => $email,
                'phone'             => '9' . $faker->randomNumber(8, true),
                'password'          => Hash::make('password123'),
                'user_type'         => 1, // Will be updated to 2 by LawyerSeeder if selected
                'active'            => 1,
                'is_verified'       => 1,
                'avatar'            => $faker->randomElement($avatars),
                'deleted'           => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        $this->command->info('[UserSeeder] Done seeding 50 users.');
    }
}
