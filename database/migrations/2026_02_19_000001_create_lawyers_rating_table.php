<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `lawyers_rating` table to store the calculated
     * reputation score and tier for each lawyer for quick access.
     * This table is populated/updated by a background job or whenever
     * a review, appointment, or compliance event changes.
     */
    public function up(): void
    {
        Schema::create('lawyers_rating', function (Blueprint $table) {
            $table->id();

            // Foreign key to lawyers table – one rating row per lawyer
            $table->foreignId('lawyer_id')
                  ->constrained('lawyers')
                  ->onDelete('cascade');

            // Normalized reputation score (0–100)
            $table->decimal('rating_score', 5, 2)->default(0.00)
                  ->comment('Normalized reputation score ranging from 0 to 100');

            // Human-readable tier derived from rating_score
            $table->string('tier', 50)->default('Wait')
                  ->comment('Current tier: Platinum, Gold, Silver, Bronze, or Wait');

            // Cached count of reviews (used for minimum-threshold logic)
            $table->integer('total_reviews')->default(0)
                  ->comment('Cached count of reviews; used to enforce minimum review threshold');

            // Sum of all appointments + question answers (drives confidence weighting)
            $table->integer('total_interactions')->default(0)
                  ->comment('Sum of appointments and Q&A answers for confidence-level weighting');

            $table->timestamps();

            // ─── Indexes ────────────────────────────────────────────────────
            $table->unique('lawyer_id');                      // one rating per lawyer
            $table->index('tier');
            $table->index('rating_score');
            $table->index(['tier', 'rating_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lawyers_rating');
    }
};
