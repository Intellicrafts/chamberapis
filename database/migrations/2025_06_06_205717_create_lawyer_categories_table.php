<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lawyer_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->foreignId('lawyer_id')->nullable()->constrained()->onDelete('cascade');
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