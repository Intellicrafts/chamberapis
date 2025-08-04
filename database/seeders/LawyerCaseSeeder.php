<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LawyerCase;
use App\Models\User;
use App\Models\Lawyer;
use App\Models\LawyerCategory;
use Faker\Factory as Faker;

class LawyerCaseSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        
        // Make sure we have the required data
        $users = User::all();
        $lawyers = Lawyer::all();
        $categories = LawyerCategory::all();
        
        if ($users->isEmpty() || $lawyers->isEmpty() || $categories->isEmpty()) {
            $this->command->info('Missing required data for LawyerCaseSeeder. Make sure users, lawyers, and categories exist.');
            return;
        }
        
        $this->command->info('Found ' . $lawyers->count() . ' lawyers, ' . $users->count() . ' users, and ' . $categories->count() . ' categories.');
        
        // Create 20 lawyer cases
        for ($i = 0; $i < 20; $i++) {
            try {
                $user = $users->random();
                $lawyer = $lawyers->random();
                $category = $categories->random();
                
                $this->command->info("Creating case with user ID: {$user->id}, lawyer ID: {$lawyer->id}, category ID: {$category->id}");
                
                LawyerCase::create([
                    'user_id' => $user->id,
                    'lawyer_id' => $lawyer->id,
                    'casename' => $faker->sentence(3),
                    'category_id' => $category->id,
                ]);
            } catch (\Exception $e) {
                $this->command->error("Error creating case: " . $e->getMessage());
            }
        }
    }
}