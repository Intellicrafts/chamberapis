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
        Schema::create('lawyer_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_id')->constrained('users')->onDelete('cascade');
            $table->string('service_code');
            $table->string('service_name');
            $table->string('billing_model')->default('flat'); // per_minute, flat, per_document
            $table->decimal('rate', 10, 2)->default(0);
            $table->string('currency')->default('INR');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('locked')->default(false);
            $table->timestamps();
            
            // A lawyer should only have one active record per service code
            $table->unique(['lawyer_id', 'service_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lawyer_services');
    }
};
