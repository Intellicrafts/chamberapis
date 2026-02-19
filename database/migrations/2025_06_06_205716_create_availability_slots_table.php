<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_id')->constrained()->onDelete('cascade');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->boolean('is_booked')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index('lawyer_id');
            $table->index('start_time');
            $table->index('is_booked');
            $table->index(['lawyer_id', 'is_booked']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('availability_slots');
    }
};