<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consultation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lawyer_id')->constrained('users')->onDelete('cascade');
            
            // Session identification
            $table->uuid('session_token')->unique();
            
            // Session status
            $table->enum('status', ['waiting', 'active', 'completed', 'expired', 'cancelled'])
                  ->default('waiting');
            
            // Timing information
            $table->timestamp('scheduled_start_time');
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('scheduled_end_time');
            $table->timestamp('actual_end_time')->nullable();
            $table->integer('duration_minutes')->default(55);
            
            // Participant tracking
            $table->timestamp('user_joined_at')->nullable();
            $table->timestamp('lawyer_joined_at')->nullable();
            
            // Session end tracking
            $table->foreignId('ended_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('end_reason', ['completed', 'timeout', 'cancelled', 'error'])->nullable();
            
            // Additional data
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('session_token');
            $table->index('appointment_id');
            $table->index(['user_id', 'lawyer_id']);
            $table->index('status');
            $table->index('scheduled_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_sessions');
    }
};
