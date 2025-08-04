<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Contact;
use App\Models\Appointment;
use App\Models\Lawyer;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users first
        User::factory(15)->create();
        
        // Create a test user for easy login if it doesn't exist
        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }
        
        // Create lawyers
        $this->call(LawyerSeeder::class);
        
        // Create lawyer categories
        $this->call(LawyerCategorySeeder::class);
        
        // Create availability slots for lawyers
        $this->call(AvailabilitySlotSeeder::class);
        
        // Create appointments
        $this->call(AppointmentSeeder::class);
        
        // Create reviews
        $this->call(ReviewSeeder::class);
        
        // Create legal queries
        $this->call(LegalQuerySeeder::class);
        
        // Create contacts
        Contact::factory(15)->create([
            'user_id' => function() {
                return User::inRandomOrder()->first()->id;
            }
        ]);
        
        // Create lawyer cases
        $this->call([
            LawyerCaseSeeder::class,
        ]);

        // Create appointments
        Appointment::factory(20)->create([
            'lawyer_id' => function() {
                return Lawyer::inRandomOrder()->first()->id;
            },
            'user_id' => function() {
                return User::inRandomOrder()->first()->id;
            }
        ]);
        
        
    }
}
