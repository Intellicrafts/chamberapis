<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('full_name');
            $table->string('email_address');
            $table->string('phone_number')->nullable();
            $table->string('company')->nullable();
            $table->string('service_interested')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status')->default('pending'); // new, pending, resolved, etc.

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
