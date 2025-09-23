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
        Schema::create('lawyer_additionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Basic Professional Information
            $table->string('enrollment_no')->unique();
            $table->integer('experience_years');
            $table->decimal('consultation_fee', 10, 2);
            
            // Practice Details (stored as JSON arrays)
            $table->json('practice_areas');
            $table->json('court_practice');
            $table->json('languages_spoken');
            
            // Professional Profile
            $table->text('professional_bio');
            $table->string('profile_photo')->nullable();
            
            // Document Paths
            $table->string('enrollment_certificate');
            $table->string('cop_certificate');
            $table->string('address_proof')->nullable();
            
            // Verification Status
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Additional Professional Information
            $table->string('bar_council_name')->nullable();
            $table->date('enrollment_date')->nullable();
            $table->string('law_firm_name')->nullable();
            $table->text('office_address')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('website_url')->nullable();
            $table->text('achievements')->nullable();
            $table->json('specializations')->nullable(); // Additional detailed specializations
            
            // Business Information
            $table->decimal('min_consultation_fee', 10, 2)->nullable();
            $table->decimal('max_consultation_fee', 10, 2)->nullable();
            $table->json('consultation_modes')->nullable(); // ['online', 'offline', 'phone']
            $table->json('available_days')->nullable(); // ['monday', 'tuesday', etc.]
            $table->time('consultation_start_time')->nullable();
            $table->time('consultation_end_time')->nullable();
            
            // Social Media and Professional Links
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('facebook_url')->nullable();
            
            // Statistics and Performance
            $table->integer('total_cases_handled')->default(0);
            $table->integer('cases_won')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->integer('total_reviews')->default(0);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('user_id');
            $table->index('enrollment_no');
            $table->index('verification_status');
            $table->index('is_active');
            $table->index('is_premium');
            $table->index('is_featured');
            $table->index(['experience_years', 'average_rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lawyer_additionals');
    }
};