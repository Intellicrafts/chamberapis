<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary key
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade'); // Maintains referential integrity
                  
            $table->string('title', 255); // Notification title
            $table->text('description')->nullable(); // Optional detailed message

            $table->enum('status', ['unread', 'read'])->default('unread'); // Efficient status tracking

            $table->timestamps(); // Includes created_at and updated_at

            // Indexes for performance
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
