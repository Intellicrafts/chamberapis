<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SimpleSeeder extends Seeder
{
    public function run()
    {
        // Create a user if it doesn't exist
        $user = DB::table('users')->where('email', 'test@example.com')->first();
        if ($user) {
            $userId = $user->id;
            $this->command->info('User already exists, using existing user.');
        } else {
            $userId = DB::table('users')->insertGetId([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Create a lawyer if it doesn't exist
        $lawyer = DB::table('lawyers')->where('email', 'lawyer@example.com')->first();
        if ($lawyer) {
            $lawyerId = $lawyer->id;
            $this->command->info('Lawyer already exists, using existing lawyer.');
        } else {
            $lawyerId = Str::uuid()->toString();
            DB::table('lawyers')->insert([
                'id' => $lawyerId,
                'full_name' => 'Test Lawyer',
                'email' => 'lawyer@example.com',
                'phone_number' => '123-456-7890',
                'password_hash' => Hash::make('password'),
                'active' => true,
                'is_verified' => true,
                'enrollment_no' => 'AB123456',
                'bar_association' => 'Test Bar Association',
                'specialization' => 'Criminal Law',
                'years_of_experience' => 10,
                'bio' => 'Test lawyer bio',
                'profile_picture_url' => null,
                'consultation_fee' => 100.00,
                'deleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("Created lawyer with ID: {$lawyerId}");
        }
        
        // Create a category if it doesn't exist
        $category = DB::table('lawyer_categories')->where('category_name', 'Test Category')->first();
        if ($category) {
            $categoryId = $category->id;
            $this->command->info('Category already exists, using existing category.');
        } else {
            $categoryId = Str::uuid()->toString();
            DB::table('lawyer_categories')->insert([
                'id' => $categoryId,
                'category_name' => 'Test Category',
                'lawyer_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("Created category with ID: {$categoryId}");
        }
        
        // Create an availability slot
        $startTime = now()->addDay()->setHour(10)->setMinute(0)->setSecond(0);
        $endTime = now()->addDay()->setHour(11)->setMinute(0)->setSecond(0);
        
        // Check if a slot already exists for this lawyer at this time
        $slot = DB::table('availability_slots')
            ->where('lawyer_id', $lawyerId)
            ->where('start_time', $startTime)
            ->where('end_time', $endTime)
            ->first();
            
        if ($slot) {
            $slotId = $slot->id;
            $this->command->info('Availability slot already exists, using existing slot.');
        } else {
            $slotId = Str::uuid()->toString();
            try {
                DB::table('availability_slots')->insert([
                    'id' => $slotId,
                    'lawyer_id' => $lawyerId,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_booked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("Created availability slot with ID: {$slotId}");
            } catch (\Exception $e) {
                $this->command->error("Error creating availability slot: " . $e->getMessage());
                // Continue execution even if this fails
            }
        }
        
        // Create a review
        $review = DB::table('reviews')
            ->where('user_id', $userId)
            ->where('lawyer_id', $lawyerId)
            ->first();
            
        if ($review) {
            $reviewId = $review->id;
            $this->command->info('Review already exists, using existing review.');
        } else {
            $reviewId = Str::uuid()->toString();
            try {
                DB::table('reviews')->insert([
                    'id' => $reviewId,
                    'user_id' => $userId,
                    'lawyer_id' => $lawyerId,
                    'rating' => 5,
                    'comment' => 'Excellent service!',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("Created review with ID: {$reviewId}");
            } catch (\Exception $e) {
                $this->command->error("Error creating review: " . $e->getMessage());
                // Continue execution even if this fails
            }
        }
        
        // Create a lawyer case
        $case = DB::table('lawyers_cases')
            ->where('user_id', $userId)
            ->where('lawyer_id', $lawyerId)
            ->where('casename', 'Test Case')
            ->first();
            
        if ($case) {
            $this->command->info('Lawyer case already exists, using existing case.');
        } else {
            try {
                DB::table('lawyers_cases')->insert([
                    'user_id' => $userId,
                    'lawyer_id' => $lawyerId,
                    'casename' => 'Test Case',
                    'category_id' => $categoryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("Created lawyer case successfully");
            } catch (\Exception $e) {
                $this->command->error("Error creating lawyer case: " . $e->getMessage());
                // Continue execution even if this fails
            }
        }
        
        $this->command->info('Simple seeding completed successfully!');
    }
}