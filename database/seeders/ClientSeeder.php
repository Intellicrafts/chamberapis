<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\User;
use App\Models\Lawyer;
use App\Models\LawyerService;

/**
 * ClientSeeder
 *
 * Seeds 15 demo client records using existing users, lawyers, and services.
 * Run order: must be after BaseDataSeeder (which creates users + lawyers).
 *
 * Usage:
 *   php artisan db:seed --class=ClientSeeder
 */
class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ── 1. Load existing IDs ─────────────────────────────────────────────
        $userIds    = User::pluck('id')->toArray();
        $lawyerIds  = Lawyer::pluck('id')->toArray();
        $serviceIds = LawyerService::pluck('id')->toArray();  // may be empty — that's fine

        // Guard: we need at least one user and one lawyer
        if (empty($userIds) || empty($lawyerIds)) {
            $this->command->warn('[ClientSeeder] No users or lawyers found — skipping. Run BaseDataSeeder first.');
            return;
        }

        $this->command->info('[ClientSeeder] Seeding 15 demo client records...');

        // ── 2. Define demo records (fixed realistic data) ─────────────────────
        $demoClients = [
            // Active clients (onboarded)
            [
                'status'       => 'active',
                'priority'     => 'high',
                'notes'        => 'Client needs urgent help with property dispute case.',
                'onboarded_at' => now()->subMonths(2),
                'closed_at'    => null,
            ],
            [
                'status'       => 'active',
                'priority'     => 'normal',
                'notes'        => 'Corporate client — quarterly legal advisory retainer.',
                'onboarded_at' => now()->subMonth(),
                'closed_at'    => null,
            ],
            [
                'status'       => 'active',
                'priority'     => 'urgent',
                'notes'        => 'Criminal defense case — bail hearing scheduled next week.',
                'onboarded_at' => now()->subWeeks(3),
                'closed_at'    => null,
            ],
            [
                'status'       => 'active',
                'priority'     => null,        // priority is nullable
                'notes'        => null,
                'onboarded_at' => now()->subDays(10),
                'closed_at'    => null,
            ],
            [
                'status'       => 'active',
                'priority'     => 'low',
                'notes'        => 'Simple document notarization request.',
                'onboarded_at' => now()->subDays(5),
                'closed_at'    => null,
            ],

            // Pending clients (not yet confirmed)
            [
                'status'       => 'pending',
                'priority'     => 'high',
                'notes'        => 'Awaiting payment confirmation before onboarding.',
                'onboarded_at' => null,
                'closed_at'    => null,
            ],
            [
                'status'       => 'pending',
                'priority'     => null,
                'notes'        => null,
                'onboarded_at' => null,
                'closed_at'    => null,
            ],
            [
                'status'       => 'pending',
                'priority'     => 'normal',
                'notes'        => 'Client submitted intake form — under review.',
                'onboarded_at' => null,
                'closed_at'    => null,
            ],

            // Inactive clients
            [
                'status'       => 'inactive',
                'priority'     => null,
                'notes'        => 'Client went silent after initial consultation.',
                'onboarded_at' => now()->subMonths(4),
                'closed_at'    => null,
            ],
            [
                'status'       => 'inactive',
                'priority'     => 'low',
                'notes'        => 'No activity for 60 days. Follow-up needed.',
                'onboarded_at' => now()->subMonths(3),
                'closed_at'    => null,
            ],

            // Closed clients (completed engagements)
            [
                'status'       => 'closed',
                'priority'     => null,
                'notes'        => 'Case resolved successfully. Client satisfied.',
                'onboarded_at' => now()->subMonths(6),
                'closed_at'    => now()->subMonth(),
            ],
            [
                'status'       => 'closed',
                'priority'     => 'normal',
                'notes'        => 'Mutual termination — client relocated abroad.',
                'onboarded_at' => now()->subMonths(5),
                'closed_at'    => now()->subWeeks(2),
            ],
            [
                'status'       => 'closed',
                'priority'     => null,
                'notes'        => null,
                'onboarded_at' => now()->subMonths(4),
                'closed_at'    => now()->subWeek(),
            ],

            // Suspended clients
            [
                'status'       => 'suspended',
                'priority'     => 'urgent',
                'notes'        => 'Suspended due to outstanding invoice — non-payment for 30 days.',
                'onboarded_at' => now()->subMonths(3),
                'closed_at'    => null,
            ],
            [
                'status'       => 'suspended',
                'priority'     => 'high',
                'notes'        => 'Compliance issue flagged — pending internal review.',
                'onboarded_at' => now()->subMonths(2),
                'closed_at'    => null,
            ],
        ];

        // ── 3. Insert records ─────────────────────────────────────────────────
        foreach ($demoClients as $clientData) {
            Client::create(array_merge($clientData, [
                // Randomly assign from existing IDs
                'user_id'   => fake()->randomElement($userIds),
                'lawyer_id' => fake()->randomElement($lawyerIds),
                // service_id is nullable — assign only if services exist
                'service_id' => !empty($serviceIds)
                    ? (fake()->boolean(70) ? fake()->randomElement($serviceIds) : null)
                    : null,
            ]));
        }

        $this->command->info('[ClientSeeder] ✓ 15 client records seeded successfully.');
    }
}
