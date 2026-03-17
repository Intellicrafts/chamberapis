<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_clients_table
 *
 * Creates the `clients` table that tracks the relationship between
 * a User (client), a Lawyer, and a specific LawyerService.
 *
 * Columns beyond user spec:
 *   - priority    : allows lawyers to flag urgency (nullable)
 *   - notes       : internal notes on the client relationship
 *   - onboarded_at: exact timestamp when the client was accepted
 *   - closed_at   : exact timestamp when the relationship was closed
 *   - deleted_at  : Laravel soft deletes (record stays in DB until hard-purged)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            // ── Primary Key ──────────────────────────────────────────────────
            $table->id();

            // ── Foreign Keys ─────────────────────────────────────────────────
            // The user who is the client
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');  // if user is deleted, client records are removed too

            // The lawyer managing this client
            $table->unsignedBigInteger('lawyer_id');
            $table->foreign('lawyer_id')
                  ->references('id')
                  ->on('lawyers')
                  ->onDelete('cascade');  // if lawyer is deleted, their client records are removed

            // The specific service the client is availing (nullable in case no service chosen yet)
            $table->unsignedBigInteger('service_id')->nullable();
            $table->foreign('service_id')
                  ->references('id')
                  ->on('lawyer_services')
                  ->onDelete('set null');  // if service is removed, keep the client record

            // ── Status & Workflow ─────────────────────────────────────────────
            // Current status of this client relationship
            $table->enum('status', ['pending', 'active', 'inactive', 'closed', 'suspended'])
                  ->default('pending')
                  ->index();              // indexed for fast filtering

            // Priority of this client — nullable as per user feedback
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
                  ->nullable()            // nullable: priority is optional
                  ->default(null)
                  ->index();

            // ── Extra Metadata ────────────────────────────────────────────────
            // Internal notes about this client relationship (visible to lawyer only)
            $table->text('notes')->nullable();

            // When the client officially started (set when status changes to active)
            $table->timestamp('onboarded_at')->nullable();

            // When the client relationship was closed (set when status changes to closed)
            $table->timestamp('closed_at')->nullable();

            // ── Standard Laravel Timestamps ───────────────────────────────────
            $table->timestamps();

            // ── Soft Deletes ──────────────────────────────────────────────────
            // Allows records to be "deleted" without losing history
            $table->softDeletes();

            // ── Composite Indexes ─────────────────────────────────────────────
            // Prevent duplicate active client rows for same user+lawyer+service
            $table->index(['user_id', 'lawyer_id'], 'clients_user_lawyer_idx');
            $table->index(['lawyer_id', 'status'],  'clients_lawyer_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
