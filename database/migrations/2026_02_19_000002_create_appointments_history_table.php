<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `appointments_history` table for precise reliability
     * metrics: no-show detection, late-join detection, and cancellation
     * tracking.  Every appointment that completes (or fails to complete)
     * should produce one row here so the rating engine can compute
     * reliability scores accurately.
     */
    public function up(): void
    {
        Schema::create('appointments_history', function (Blueprint $table) {
            $table->id();

            // ── Core relationships ───────────────────────────────────────────
            $table->foreignId('appointment_id')
                  ->constrained('appointments')
                  ->onDelete('cascade')
                  ->comment('Reference to the original appointment');

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('User who booked the appointment');

            $table->foreignId('lawyer_id')
                  ->constrained('lawyers')
                  ->onDelete('cascade')
                  ->comment('Lawyer assigned to the appointment');

            // ── Outcome / status ─────────────────────────────────────────────
            $table->string('status', 50)->default('completed')
                  ->comment('Final outcome: completed, no_show, late, cancelled, rescheduled');

            // ── Join timestamps (for lateness / no-show detection) ───────────
            $table->timestamp('lawyer_joined_at')->nullable()
                  ->comment('Exact time the lawyer joined the call; null = no-show');

            $table->timestamp('user_joined_at')->nullable()
                  ->comment('Exact time the user joined; null = user no-show');

            // ── Payment flag ────────────────────────────────────────────────
            $table->boolean('is_paid')->default(false)
                  ->comment('TRUE for paid consultations; FALSE for free/demo sessions');

            // ── Cancellation detail ──────────────────────────────────────────
            $table->text('cancellation_reason')->nullable()
                  ->comment('Free-text reason: "User request", "Lawyer cancel", "System", etc.');

            // ── Flexible JSON snapshot of appointment data at resolution time ─
            $table->json('appointment_data')->nullable()
                  ->comment('JSON snapshot of the original appointment row at history-creation time');

            $table->timestamps();

            // ─── Indexes ────────────────────────────────────────────────────
            $table->index('appointment_id');
            $table->index('user_id');
            $table->index('lawyer_id');
            $table->index('status');
            $table->index('is_paid');
            $table->index(['lawyer_id', 'status']);
            $table->index(['lawyer_id', 'is_paid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments_history');
    }
};
