<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `compliance_events` table to track negative compliance
     * history (misconduct, disputes) for lawyers.  Each row represents
     * one reported / verified negative event that deducts points from
     * the lawyer's reputation score.
     */
    public function up(): void
    {
        Schema::create('compliance_events', function (Blueprint $table) {
            $table->id();

            // Lawyer affected by this compliance event
            $table->foreignId('lawyer_id')
                  ->constrained('lawyers')
                  ->onDelete('cascade')
                  ->comment('Lawyer against whom this event is recorded');

            // Type of compliance event (drives how many points are deducted)
            $table->string('event_type', 50)->notNull()
                  ->comment('minor_complaint | verified_misconduct | refund_dispute_loss');

            // Human-readable description / evidence summary
            $table->text('description')->nullable()
                  ->comment('Detailed description or evidence summary of the event');

            // When the event actually occurred (not when it was recorded)
            $table->timestamp('occurred_at')->notNull()
                  ->comment('Actual date/time of the incident');

            // Audit timestamps
            $table->timestamp('created_at')->nullable()->useCurrent()
                  ->comment('When this record was created in the system');

            // When (and if) the event was resolved / dismissed
            $table->timestamp('resolved_at')->nullable()
                  ->comment('Null = still active/open; set when resolved or dismissed');

            // ─── Indexes ────────────────────────────────────────────────────
            $table->index('lawyer_id');
            $table->index('event_type');
            $table->index('occurred_at');
            $table->index('resolved_at');
            $table->index(['lawyer_id', 'event_type']);
            $table->index(['lawyer_id', 'resolved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_events');
    }
};
