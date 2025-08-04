<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('legal_queries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->text('question_text');
            $table->text('ai_response')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('legal_queries');
    }
};