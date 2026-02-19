<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `specialization_scores` table used to store separate
     * reputation scores per practice area / specialization category
     * (Criminal, Civil, Family, Corporate, etc.).
     *
     * One row per lawyer–specialization pair; enforced by a unique key.
     */
    public function up(): void
    {
        Schema::create('specialization_scores', function (Blueprint $table) {
            $table->id();

            // Lawyer owning this specialization score
            $table->foreignId('lawyer_id')
                  ->constrained('lawyers')
                  ->onDelete('cascade')
                  ->comment('Lawyer whose specialization-level score this is');

            // Name of the practice area / specialization (e.g. "Criminal", "Civil")
            $table->string('specialization', 100)->notNull()
                  ->comment('Practice-area label: Criminal, Civil, Family, Corporate, etc.');

            // Computed score for this specific specialization (0–100 scale)
            $table->decimal('score', 5, 2)->notNull()
                  ->comment('Reputation score (0–100) for this specific specialization');

            // Auto-maintained timestamp; updated whenever the score is recalculated
            $table->timestamp('last_updated')->nullable()->useCurrent()
                  ->comment('Timestamp of the last score recalculation for this row');

            // ─── Constraints & Indexes ───────────────────────────────────────
            // Enforce one row per lawyer + specialization combination
            $table->unique(['lawyer_id', 'specialization'], 'unique_lawyer_spec');

            $table->index('lawyer_id');
            $table->index('specialization');
            $table->index(['specialization', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specialization_scores');
    }
};
