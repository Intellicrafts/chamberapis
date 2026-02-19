<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

/**
 * BaseDataSeeder
 *
 * Creates the minimum seed data required for the Rating & Reputation
 * system seeders to work:  users + lawyers + appointments.
 *
 * Uses raw DB inserts so nothing breaks due to fillable restrictions.
 * Safe to run multiple times (idempotent checks included).
 */
class BaseDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_IN');

        // ── 1. Create 10 users ───────────────────────────────────────────────
        $this->command->info('Creating users...');
        $userIds = [];

        $usersData = [
            ['name' => 'Arjun Sharma',    'email' => 'arjun.sharma@example.com'],
            ['name' => 'Priya Verma',     'email' => 'priya.verma@example.com'],
            ['name' => 'Rahul Gupta',     'email' => 'rahul.gupta@example.com'],
            ['name' => 'Sunita Patel',    'email' => 'sunita.patel@example.com'],
            ['name' => 'Vikram Singh',    'email' => 'vikram.singh@example.com'],
            ['name' => 'Neha Joshi',      'email' => 'neha.joshi@example.com'],
            ['name' => 'Amit Khanna',     'email' => 'amit.khanna@example.com'],
            ['name' => 'Kavya Reddy',     'email' => 'kavya.reddy@example.com'],
            ['name' => 'Mohit Yadav',     'email' => 'mohit.yadav@example.com'],
            ['name' => 'Deepa Nair',      'email' => 'deepa.nair@example.com'],
        ];

        foreach ($usersData as $userData) {
            $existing = DB::table('users')->where('email', $userData['email'])->first();
            if ($existing) {
                $userIds[] = $existing->id;
                $this->command->line("  – User {$userData['email']} already exists (id={$existing->id})");
                continue;
            }

            $id = DB::table('users')->insertGetId([
                'name'       => $userData['name'],
                'email'      => $userData['email'],
                'password'   => Hash::make('password123'),
                'user_type'  => 1,
                'active'     => true,
                'is_verified'=> true,
                'deleted'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $userIds[] = $id;
            $this->command->line("  ✔ User created: {$userData['name']} (id={$id})");
        }

        // ── 2. Create 10 lawyers ─────────────────────────────────────────────
        $this->command->info('Creating lawyers...');
        $lawyerIds = [];

        $lawyersData = [
            ['name' => 'Adv. Rajesh Kumar',   'email' => 'rajesh.kumar@lawfirm.com',   'spec' => 'Criminal Law',         'exp' => 15, 'lic' => 'DL2001CR001'],
            ['name' => 'Adv. Meera Iyer',     'email' => 'meera.iyer@lawfirm.com',     'spec' => 'Family Law',           'exp' => 12, 'lic' => 'MH2005FL002'],
            ['name' => 'Adv. Sanjay Mehta',   'email' => 'sanjay.mehta@lawfirm.com',   'spec' => 'Corporate Law',        'exp' => 20, 'lic' => 'GJ2000CL003'],
            ['name' => 'Adv. Pooja Nambiar',  'email' => 'pooja.nambiar@lawfirm.com',  'spec' => 'Civil Law',            'exp' =>  8, 'lic' => 'KL2010CL004'],
            ['name' => 'Adv. Ravi Bhatia',    'email' => 'ravi.bhatia@lawfirm.com',    'spec' => 'Property Law',         'exp' => 18, 'lic' => 'UP2002PL005'],
            ['name' => 'Adv. Nandini Das',    'email' => 'nandini.das@lawfirm.com',    'spec' => 'Labour Law',           'exp' =>  6, 'lic' => 'WB2014LL006'],
            ['name' => 'Adv. Kiran Shah',     'email' => 'kiran.shah@lawfirm.com',     'spec' => 'Intellectual Property','exp' => 10, 'lic' => 'MH2009IP007'],
            ['name' => 'Adv. Suresh Pillai',  'email' => 'suresh.pillai@lawfirm.com',  'spec' => 'Taxation Law',         'exp' => 22, 'lic' => 'KL2001TL008'],
            ['name' => 'Adv. Anita Singh',    'email' => 'anita.singh@lawfirm.com',    'spec' => 'Consumer Protection',  'exp' =>  5, 'lic' => 'RJ2017CP009'],
            ['name' => 'Adv. Vijay Deshpande','email' => 'vijay.deshpande@lawfirm.com','spec' => 'Constitutional Law',   'exp' => 25, 'lic' => 'MH1998CL010'],
        ];

        foreach ($lawyersData as $lawyerData) {
            $existing = DB::table('lawyers')->where('email', $lawyerData['email'])->first();
            if ($existing) {
                $lawyerIds[] = $existing->id;
                $this->command->line("  – Lawyer {$lawyerData['email']} already exists (id={$existing->id})");
                continue;
            }

            $id = DB::table('lawyers')->insertGetId([
                'full_name'          => $lawyerData['name'],
                'email'              => $lawyerData['email'],
                'phone_number'       => '9' . rand(100000000, 999999999),
                'password_hash'      => Hash::make('lawyer123'),
                'active'             => true,
                'is_verified'        => true,
                'license_number'     => $lawyerData['lic'],
                'bar_association'    => 'Bar Council of India',
                'specialization'     => $lawyerData['spec'],
                'years_of_experience'=> $lawyerData['exp'],
                'bio'                => "Experienced {$lawyerData['spec']} practitioner with {$lawyerData['exp']} years of expertise.",
                'profile_picture_url'=> null,
                'consultation_fee'   => round(rand(50, 200) * 10 / 100, 2) * 100,
                'deleted'            => false,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
            $lawyerIds[] = $id;
            $this->command->line("  ✔ Lawyer created: {$lawyerData['name']} (id={$id})");
        }

        // ── 3. Create 20 appointments ────────────────────────────────────────
        $this->command->info('Creating appointments...');
        $statuses   = ['scheduled', 'completed', 'cancelled', 'completed', 'completed'];
        $inserted   = 0;

        for ($i = 0; $i < 20; $i++) {
            $userId   = $userIds[$i % count($userIds)];
            $lawyerId = $lawyerIds[$i % count($lawyerIds)];
            $apptTime = now()->subDays(rand(1, 90))->setHour(rand(9,17))->setMinute(0)->setSecond(0);

            DB::table('appointments')->insert([
                'user_id'          => $userId,
                'lawyer_id'        => $lawyerId,
                'appointment_time' => $apptTime,
                'duration_minutes' => 30,
                'status'           => $statuses[$i % count($statuses)],
                'meeting_link'     => 'https://meet.meravakil.com/room-' . strtolower(substr(md5($i), 0, 8)),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
            $inserted++;
        }
        $this->command->line("  ✔ {$inserted} appointments created.");

        $this->command->info('[BaseDataSeeder] Done.');
    }
}
