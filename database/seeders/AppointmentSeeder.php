<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users and lawyers
        $userIds = \App\Models\User::pluck('id')->toArray();
        $lawyerIds = \App\Models\Lawyer::pluck('id')->toArray();
        
        // Make sure we have users and lawyers
        if (empty($userIds) || empty($lawyerIds)) {
            $this->command->info('No users or lawyers found. Please run UserSeeder and LawyerSeeder first.');
            return;
        }
        
        // Create 15 demo appointments
        Appointment::factory()->count(15)->make()->each(function ($appointment) use ($userIds, $lawyerIds) {
            $appointment->user_id = fake()->randomElement($userIds);
            $appointment->lawyer_id = fake()->randomElement($lawyerIds);
            $appointment->save();
        });
    }
}
