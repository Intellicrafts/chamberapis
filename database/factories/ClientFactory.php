<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use App\Models\Lawyer;
use App\Models\LawyerService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ClientFactory
 *
 * Generates realistic fake Client records for testing and seeding.
 * Picks user_id, lawyer_id, and service_id from existing records,
 * so BaseDataSeeder must run before this factory is used.
 */
class ClientFactory extends Factory
{
    // Link this factory to the Client model
    protected $model = Client::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Map status to probability weights — most should be active or pending
        $status = $this->faker->randomElement([
            'pending',   // 20%
            'pending',
            'active',    // 40%
            'active',
            'active',
            'active',
            'inactive',  // 15%
            'inactive',
            'closed',    // 15%
            'closed',
            'suspended', // 10%
        ]);

        // Set onboarded_at only when active, closed, or suspended
        $onboardedAt = in_array($status, ['active', 'closed', 'suspended'])
            ? $this->faker->dateTimeBetween('-6 months', '-1 week')
            : null;

        // Set closed_at only when closed
        $closedAt = $status === 'closed'
            ? $this->faker->dateTimeBetween($onboardedAt ?? '-3 months', 'now')
            : null;

        return [
            // FKs will be overridden in the seeder; defaults here for standalone use
            'user_id'      => User::inRandomOrder()->value('id') ?? 1,
            'lawyer_id'    => Lawyer::inRandomOrder()->value('id') ?? 1,
            'service_id'   => LawyerService::inRandomOrder()->value('id'), // nullable
            'status'       => $status,
            'priority'     => $this->faker->optional(0.6)->randomElement(Client::getPriorities()), // 60% have a priority
            'notes'        => $this->faker->optional(0.5)->paragraph(),    // 50% have notes
            'onboarded_at' => $onboardedAt,
            'closed_at'    => $closedAt,
        ];
    }

    // ── State modifiers (for fine-grained factory usage) ─────────────────────

    /** Return a factory state where the client is active. */
    public function active(): static
    {
        return $this->state(fn () => [
            'status'       => Client::STATUS_ACTIVE,
            'onboarded_at' => now()->subDays(rand(1, 90)),
            'closed_at'    => null,
        ]);
    }

    /** Return a factory state where the client is pending. */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status'       => Client::STATUS_PENDING,
            'onboarded_at' => null,
            'closed_at'    => null,
        ]);
    }

    /** Return a factory state where the client is closed. */
    public function closed(): static
    {
        return $this->state(fn () => [
            'status'       => Client::STATUS_CLOSED,
            'onboarded_at' => now()->subMonths(3),
            'closed_at'    => now()->subDays(rand(1, 30)),
        ]);
    }
}
