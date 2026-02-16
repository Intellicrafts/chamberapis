<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lawyers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('password_hash');
            $table->boolean('active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->string('license_number')->unique();
            $table->string('bar_association')->nullable();
            $table->string('specialization')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->text('bio')->nullable();
            $table->text('profile_picture_url')->nullable();
            $table->decimal('consultation_fee', 10, 2)->default(0.00);
            $table->timestamps();
            $table->boolean('deleted')->default(false);
            
            // Indexes
            $table->index('email');
            $table->index('license_number');
            $table->index('specialization');
            $table->index('active');
            $table->index('is_verified');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lawyers');
    }
};