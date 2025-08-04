<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lawyer_id');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->boolean('is_booked')->default(false);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('lawyer_id')->references('id')->on('lawyers')->onDelete('cascade');
            
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