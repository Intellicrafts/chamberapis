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
        Schema::create('chat_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('chat_session_id')->constrained('chat_sessions')->onDelete('cascade');
            $table->enum('sender', ['user', 'bot', 'system'])->default('user');
            $table->text('message')->nullable();
            $table->string('event_type')->default('message'); // message, action, suggestion
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('chat_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_events');
    }
};
