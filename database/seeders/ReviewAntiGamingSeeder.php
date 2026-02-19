<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\Lawyer;
use App\Models\User;

class ReviewAntiGamingSeeder extends Seeder
{
    /**
     * Back-fill `ip_address` and `device_id` on the existing reviews table
     * AND create 20 brand-new review rows that include the new anti-gaming
     * fields from the start.
     *
     * Running this seeder a second time is safe because we check for
     * duplicate user+lawyer pairs before inserting new rows.
     */
    public function run(): void
    {
        $users   = User::orderBy('id')->get();
        $lawyers = Lawyer::orderBy('id')->get();

        if ($users->isEmpty() || $lawyers->isEmpty()) {
            $this->command->warn('[ReviewAntiGamingSeeder] No users or lawyers found – skipping.');
            return;
        }

        // ── 1. Back-fill existing reviews that have null ip / device_id ───────
        $backfilled = Review::whereNull('ip_address')->get();
        foreach ($backfilled as $rev) {
            $rev->update([
                'ip_address' => $this->fakeIp(),
                'device_id'  => $this->fakeDeviceId(),
            ]);
        }
        $this->command->line("  ↺ Back-filled {$backfilled->count()} existing review rows.");

        // ── 2. Insert 20 new reviews with anti-gaming fields populated ─────────
        $comments = [
            'Exceptional legal advice, highly recommend!',
            'Very professional and thorough in handling my case.',
            'Good knowledge but communication could be better.',
            'Resolved my property dispute quickly.',
            'Fair pricing, excellent service.',
            'Patient listener and great strategist.',
            'Would hire again without any hesitation.',
            'The consultation was detailed and very helpful.',
            'Helped me understand my rights clearly.',
            'Great experience from start to finish.',
            'Straightforward advice that saved me time and money.',
            'Very responsive to messages and calls.',
            'Handled my family matter with sensitivity.',
            'Could improve on meeting punctuality.',
            'Competent lawyer, covered all angles of my case.',
            'Felt guided throughout the entire process.',
            'Answered all my questions without rushing.',
            'Strong courtroom presence, confident lawyer.',
            'Delivered exactly what was promised.',
            'Highly knowledgeable in corporate law matters.',
        ];

        $inserted = 0;

        for ($i = 0; $i < 20; $i++) {
            $user   = $users[$i % $users->count()];
            $lawyer = $lawyers[$i % $lawyers->count()];

            // Avoid creating exact duplicate user+lawyer pairs
            if (Review::where('user_id', $user->id)->where('lawyer_id', $lawyer->id)->exists()) {
                $this->command->line("  – Review for user_id={$user->id} / lawyer_id={$lawyer->id} already exists – skipping.");
                continue;
            }

            Review::create([
                'user_id'   => $user->id,
                'lawyer_id' => $lawyer->id,
                'rating'    => rand(3, 5),
                'comment'   => $comments[$i],
                'ip_address' => $this->fakeIp(),
                'device_id'  => $this->fakeDeviceId(),
            ]);

            $inserted++;
            $this->command->line("  ✔ Review #{$inserted}: user_id={$user->id}, lawyer_id={$lawyer->id}");
        }

        $this->command->info("[ReviewAntiGamingSeeder] Done – {$inserted} new rows inserted.");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function fakeIp(): string
    {
        // Returns a random IPv4 in the 192.168.x.x or 10.x.x.x range
        return rand(0, 1) === 0
            ? '192.168.' . rand(0, 255) . '.' . rand(1, 254)
            : '10.'      . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254);
    }

    private function fakeDeviceId(): string
    {
        return 'DEV-' . strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 16));
    }
}
