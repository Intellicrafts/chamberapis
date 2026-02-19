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
        Schema::table('appointments', function (Blueprint $table) {
            // Add consultation-related fields
            $table->integer('consultation_duration_minutes')->default(55);
            $table->boolean('consultation_enabled')->default(true);
            $table->timestamp('consultation_join_time')->nullable();
            $table->enum('consultation_status', ['pending', 'ready', 'in_progress', 'completed', 'missed'])
                  ->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'consultation_duration_minutes',
                'consultation_enabled',
                'consultation_join_time',
                'consultation_status'
            ]);
        });
    }
};
