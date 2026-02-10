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
        Schema::create('consultation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            
            // Message type and content
            $table->enum('sender_type', ['user', 'lawyer', 'system'])->default('user');
            $table->enum('message_type', ['text', 'file', 'system', 'notification'])->default('text');
            $table->text('content');
            
            // File attachment
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->bigInteger('file_size')->nullable(); // in bytes
            
            // Read status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            // Additional data
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('consultation_session_id');
            $table->index('sender_id');
            $table->index('created_at');
            $table->index(['consultation_session_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_messages');
    }
};
