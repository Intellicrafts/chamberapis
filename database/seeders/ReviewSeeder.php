<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users and lawyers
        $users = \App\Models\User::all();
        $lawyers = \App\Models\Lawyer::all();
        
        // Make sure we have users and lawyers
        if ($users->isEmpty() || $lawyers->isEmpty()) {
            $this->command->info('No users or lawyers found. Please run UserSeeder and LawyerSeeder first.');
            return;
        }
        
        $this->command->info('Found ' . $lawyers->count() . ' lawyers and ' . $users->count() . ' users.');
        
        // Create reviews (3 per lawyer if possible)
        foreach ($lawyers as $lawyer) {
            $this->command->info("Creating reviews for lawyer ID: {$lawyer->id}");
            
            // Get random users for this lawyer (up to 3)
            $randomUsers = $users->random(min(3, $users->count()));
            
            foreach ($randomUsers as $user) {
                try {
                    // Check if the lawyer ID is valid
                    if (!\App\Models\Lawyer::where('id', $lawyer->id)->exists()) {
                        $this->command->error("Lawyer with ID {$lawyer->id} does not exist in the database.");
                        continue;
                    }
                    
                    $rating = rand(3, 5); // Mostly positive reviews (3-5 stars)
                    $comments = [
                        'Excellent service and very knowledgeable.',
                        'Very professional and helpful.',
                        'Helped me resolve my case quickly.',
                        'Great communication throughout the process.',
                        'Would definitely recommend to others.',
                        'Extremely satisfied with the service.',
                        'Very responsive and attentive to details.'
                    ];
                    
                    \App\Models\Review::create([
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'user_id' => $user->id,
                        'lawyer_id' => $lawyer->id,
                        'rating' => $rating,
                        'comment' => $comments[array_rand($comments)]
                    ]);
                } catch (\Exception $e) {
                    $this->command->error("Error creating review for lawyer {$lawyer->id} by user {$user->id}: " . $e->getMessage());
                }
            }
        }
    }
}
