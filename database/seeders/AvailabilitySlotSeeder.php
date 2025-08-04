<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AvailabilitySlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing lawyers
        $lawyers = \App\Models\Lawyer::all();
        
        // Make sure we have lawyers
        if ($lawyers->isEmpty()) {
            $this->command->info('No lawyers found. Please run LawyerSeeder first.');
            return;
        }
        
        $this->command->info('Found ' . $lawyers->count() . ' lawyers.');
        
        // Create 5 availability slots for each lawyer
        foreach ($lawyers as $lawyer) {
            // Debug output
            $this->command->info("Creating slots for lawyer ID: {$lawyer->id}");
            
            for ($i = 0; $i < 5; $i++) {
                // Generate a random start time in the next 14 days
                $startTime = now()->addDays(rand(1, 14))->setHour(rand(9, 16))->setMinute(0)->setSecond(0);
                
                // End time is 1 hour after start time
                $endTime = clone $startTime;
                $endTime->addHour();
                
                try {
                    // Check if the lawyer ID is valid
                    if (\App\Models\Lawyer::where('id', $lawyer->id)->exists()) {
                        \App\Models\AvailabilitySlot::create([
                            'id' => \Illuminate\Support\Str::uuid()->toString(),
                            'lawyer_id' => $lawyer->id,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'is_booked' => false
                        ]);
                    } else {
                        $this->command->error("Lawyer with ID {$lawyer->id} does not exist in the database.");
                    }
                } catch (\Exception $e) {
                    $this->command->error("Error creating slot for lawyer {$lawyer->id}: " . $e->getMessage());
                }
            }
        }
    }
}
