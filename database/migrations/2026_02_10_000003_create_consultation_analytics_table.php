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
        Schema::create('consultation_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lawyer_id')->constrained('users')->onDelete('cascade');
            
            // Session metrics
            $table->date('consultation_date');
            $table->integer('duration_minutes');
            $table->integer('message_count')->default(0);
            $table->integer('user_message_count')->default(0);
            $table->integer('lawyer_message_count')->default(0);
            
            // Engagement metrics
            $table->timestamp('first_message_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->integer('response_time_seconds')->nullable(); // Average response time
            
            // Satisfaction metrics
            $table->integer('user_satisfaction')->nullable(); // 1-5 rating
            $table->text('user_feedback')->nullable();
            $table->text('lawyer_notes')->nullable();
            
            // Financial
            $table->decimal('consultation_fee', 10, 2)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            
            // Session quality indicators
            $table->integer('connection_issues')->default(0);
            $table->boolean('completed_successfully')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('consultation_session_id');
            $table->index('appointment_id');
            $table->index('consultation_date');
            $table->index(['user_id', 'consultation_date']);
            $table->index(['lawyer_id', 'consultation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_analytics');
    }
};
