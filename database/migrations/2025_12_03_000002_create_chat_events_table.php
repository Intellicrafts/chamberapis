<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates the events table which stores individual
     * chat events/messages from AI agentic chatbot conversations.
     * Each event is linked to a session and represents a single
     * message or interaction in the conversation flow.
     * 
     * Event types include:
     * - chat: For conversation messages (user messages, AI responses)
     * - system: For system events
     * - action: For user actions
     * 
     * Event data is stored as JSON to support flexible message structures
     * including text, metadata, and additional context.
     * 
     * Note: This table may already exist in your database.
     * This migration is for documentation and setup purposes only.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('event_type');
            $table->string('event_name')->nullable();
            $table->json('event_data')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('referrer')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->foreign('session_id')
                  ->references('id')
                  ->on('sessions')
                  ->onDelete('cascade');

            $table->index(['user_id', 'event_type']);
            $table->index(['session_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index('session_id');
            $table->index('event_type');
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
