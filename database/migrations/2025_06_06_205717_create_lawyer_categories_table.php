<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lawyer_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('category_name')->unique();
            $table->uuid('lawyer_id')->nullable(); // This might need revision based on your logic
            $table->timestamps();
            
            // Indexes
            $table->index('category_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lawyer_categories');
    }
};