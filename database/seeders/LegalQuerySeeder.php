<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LegalQuerySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users
        $userIds = \App\Models\User::pluck('id')->toArray();
        
        // Make sure we have users
        if (empty($userIds)) {
            $this->command->info('No users found. Please run UserSeeder first.');
            return;
        }
        
        // Create 2-3 legal queries for each user
        foreach ($userIds as $userId) {
            $queryCount = rand(2, 3);
            
            for ($i = 0; $i < $queryCount; $i++) {
                \App\Models\LegalQuery::factory()->create([
                    'user_id' => $userId
                ]);
            }
        }
    }
}
