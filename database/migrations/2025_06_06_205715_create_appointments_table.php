<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('lawyer_id')->constrained()->onDelete('cascade'); 
            $table->timestamp('appointment_time');
            $table->integer('duration_minutes')->default(30);
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled, no-show
            $table->text('meeting_link')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('lawyer_id');
            $table->index('appointment_time');
            $table->index('status');
            $table->index(['lawyer_id', 'appointment_time']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
};