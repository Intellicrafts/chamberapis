<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->uuid('lawyer_id');
            $table->foreign('lawyer_id')->references('id')->on('lawyers')->onDelete('cascade');

            $table->integer('rating')->check('rating >= 1 AND rating <= 5');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'lawyer_id']);
            $table->index('rating');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};