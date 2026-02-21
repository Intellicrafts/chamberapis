<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Lawyer;
use App\Models\LawyerCategory;
use App\Models\AvailabilitySlot;
use App\Models\LawyerCase;
use App\Models\Review;
use Faker\Factory as Faker;

class FixedSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        
        // Create users
        $this->command->info('Creating users...');
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $user = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'),
            ]);
            $users[] = $user;
        }
        
        // Create a test user
        if (!User::where('email', 'test@example.com')->exists()) {
            User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }
        
        // Create lawyers
        $this->command->info('Creating lawyers...');
        $lawyers = [];
        $specializations = [
            'Criminal Law', 'Family Law', 'Corporate Law', 'Intellectual Property', 
            'Real Estate Law', 'Tax Law', 'Immigration Law', 'Labor Law', 
            'Environmental Law', 'Constitutional Law', 'Civil Rights Law'
        ];
        
        for ($i = 0; $i < 5; $i++) {
            $uuid = Str::uuid()->toString();
            
            // Insert directly into the database to bypass fillable restrictions
            $lawyerId = \DB::table('lawyers')->insertGetId([
                'id' => $uuid,
                'full_name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'phone_number' => $faker->phoneNumber,
                'password_hash' => bcrypt('password'),
                'active' => true,
                'is_verified' => true,
                'enrollment_no' => $faker->unique()->regexify('[A-Z]{2}[0-9]{6}'),
                'bar_association' => $faker->randomElement(['State Bar Association', 'American Bar Association', 'County Bar Association']),
                'specialization' => $faker->randomElement($specializations),
                'years_of_experience' => $faker->numberBetween(1, 30),
                'bio' => $faker->paragraph(3),
                'profile_picture_url' => null,
                'consultation_fee' => $faker->randomFloat(2, 50, 500),
                'deleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Retrieve the lawyer model
            $lawyer = Lawyer::find($uuid);
            $lawyers[] = $lawyer;
            
            $this->command->info("Created lawyer with ID: {$uuid}");
        }
        
        // Create lawyer categories
        $this->command->info('Creating lawyer categories...');
        $categories = [];
        $categoryNames = [
            'Criminal Law', 'Family Law', 'Corporate Law', 'Intellectual Property', 
            'Real Estate Law', 'Tax Law', 'Immigration Law', 'Labor Law', 
            'Environmental Law', 'Constitutional Law', 'Civil Rights Law',
            'Personal Injury', 'Medical Malpractice', 'Bankruptcy', 'Estate Planning'
        ];
        
        foreach ($categoryNames as $categoryName) {
            // Check if category already exists
            $existingCategory = LawyerCategory::where('category_name', $categoryName)->first();
            
            if ($existingCategory) {
                $this->command->info("Category '{$categoryName}' already exists, skipping...");
                $categories[] = $existingCategory;
            } else {
                $uuid = Str::uuid()->toString();
                
                // Insert directly into the database
                \DB::table('lawyer_categories')->insert([
                    'id' => $uuid,
                    'category_name' => $categoryName,
                    'lawyer_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Retrieve the category model
                $category = LawyerCategory::find($uuid);
                $categories[] = $category;
                
                $this->command->info("Created category '{$categoryName}' with ID: {$uuid}");
            }
        }
        
        // Create availability slots
        $this->command->info('Creating availability slots...');
        foreach ($lawyers as $lawyer) {
            if (!$lawyer) {
                $this->command->error("Lawyer is null, skipping...");
                continue;
            }
            
            $this->command->info("Creating slots for lawyer: {$lawyer->full_name} (ID: {$lawyer->id})");
            
            for ($i = 0; $i < 5; $i++) {
                // Generate a random start time in the next 14 days
                $startTime = now()->addDays(rand(1, 14))->setHour(rand(9, 16))->setMinute(0)->setSecond(0);
                
                // End time is 1 hour after start time
                $endTime = clone $startTime;
                $endTime->addHour();
                
                $uuid = Str::uuid()->toString();
                
                try {
                    // Insert directly into the database
                    \DB::table('availability_slots')->insert([
                        'id' => $uuid,
                        'lawyer_id' => $lawyer->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'is_booked' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $this->command->info("Created slot with ID: {$uuid} for lawyer: {$lawyer->full_name}");
                } catch (\Exception $e) {
                    $this->command->error("Error creating slot: " . $e->getMessage());
                }
            }
        }
        
        // Create reviews
        $this->command->info('Creating reviews...');
        $comments = [
            'Excellent service and very knowledgeable.',
            'Very professional and helpful.',
            'Helped me resolve my case quickly.',
            'Great communication throughout the process.',
            'Would definitely recommend to others.',
            'Extremely satisfied with the service.',
            'Very responsive and attentive to details.'
        ];
        
        foreach ($lawyers as $lawyer) {
            if (!$lawyer) {
                $this->command->error("Lawyer is null, skipping reviews...");
                continue;
            }
            
            $this->command->info("Creating reviews for lawyer: {$lawyer->full_name} (ID: {$lawyer->id})");
            
            // Get 3 random users for this lawyer
            $randomUsers = array_rand($users, min(3, count($users)));
            if (!is_array($randomUsers)) {
                $randomUsers = [$randomUsers];
            }
            
            foreach ($randomUsers as $userIndex) {
                $uuid = Str::uuid()->toString();
                $rating = rand(3, 5);
                $comment = $comments[array_rand($comments)];
                
                try {
                    // Insert directly into the database
                    \DB::table('reviews')->insert([
                        'id' => $uuid,
                        'user_id' => $users[$userIndex]->id,
                        'lawyer_id' => $lawyer->id,
                        'rating' => $rating,
                        'comment' => $comment,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $this->command->info("Created review with ID: {$uuid} for lawyer: {$lawyer->full_name} by user: {$users[$userIndex]->name}");
                } catch (\Exception $e) {
                    $this->command->error("Error creating review: " . $e->getMessage());
                }
            }
        }
        
        // Create lawyer cases
        $this->command->info('Creating lawyer cases...');
        for ($i = 0; $i < 10; $i++) {
            $user = $users[array_rand($users)];
            $lawyer = $lawyers[array_rand($lawyers)];
            $category = $categories[array_rand($categories)];
            
            if (!$lawyer || !$category) {
                $this->command->error("Lawyer or category is null, skipping case...");
                continue;
            }
            
            $this->command->info("Creating case for user: {$user->name}, lawyer: {$lawyer->full_name}, category: {$category->category_name}");
            
            try {
                // Insert directly into the database
                \DB::table('lawyers_cases')->insert([
                    'user_id' => $user->id,
                    'lawyer_id' => $lawyer->id,
                    'casename' => $faker->sentence(3),
                    'category_id' => $category->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->command->info("Created case for user: {$user->name}, lawyer: {$lawyer->full_name}, category: {$category->category_name}");
            } catch (\Exception $e) {
                $this->command->error("Error creating case: " . $e->getMessage());
            }
        }
        
        $this->command->info('Seeding completed successfully!');
    }
}