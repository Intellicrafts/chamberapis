<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LawyerAdditional extends Model
{
    use HasFactory;

    protected $table = 'lawyer_additionals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'enrollment_no',
        'experience_years',
        'consultation_fee',
        'practice_areas',
        'court_practice',
        'languages_spoken',
        'professional_bio',
        'profile_photo',
        'enrollment_certificate',
        'cop_certificate',
        'address_proof',
        'verification_status',
        'verification_notes',
        'verified_at',
        'verified_by',
        'bar_council_name',
        'enrollment_date',
        'law_firm_name',
        'office_address',
        'office_phone',
        'website_url',
        'achievements',
        'specializations',
        'min_consultation_fee',
        'max_consultation_fee',
        'consultation_modes',
        'available_days',
        'consultation_start_time',
        'consultation_end_time',
        'linkedin_url',
        'twitter_url',
        'facebook_url',
        'total_cases_handled',
        'cases_won',
        'average_rating',
        'total_reviews',
        'is_premium',
        'is_featured',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'practice_areas' => 'array',
        'court_practice' => 'array',
        'languages_spoken' => 'array',
        'specializations' => 'array',
        'consultation_modes' => 'array',
        'available_days' => 'array',
        'experience_years' => 'integer',
        'consultation_fee' => 'decimal:2',
        'min_consultation_fee' => 'decimal:2',
        'max_consultation_fee' => 'decimal:2',
        'total_cases_handled' => 'integer',
        'cases_won' => 'integer',
        'average_rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'is_premium' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'enrollment_date' => 'date',
        'consultation_start_time' => 'datetime:H:i',
        'consultation_end_time' => 'datetime:H:i',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'verification_notes',
    ];

    /**
     * Get the user that owns the lawyer details.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who verified this lawyer.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope a query to only include verified lawyers.
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope a query to only include active lawyers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include premium lawyers.
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope a query to only include featured lawyers.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to filter by practice areas.
     */
    public function scopeByPracticeArea($query, $practiceArea)
    {
        return $query->whereJsonContains('practice_areas', $practiceArea);
    }

    /**
     * Scope a query to filter by court practice.
     */
    public function scopeByCourtPractice($query, $court)
    {
        return $query->whereJsonContains('court_practice', $court);
    }

    /**
     * Scope a query to filter by languages spoken.
     */
    public function scopeByLanguage($query, $language)
    {
        return $query->whereJsonContains('languages_spoken', $language);
    }

    /**
     * Scope a query to filter by experience range.
     */
    public function scopeByExperience($query, $minYears, $maxYears = null)
    {
        $query->where('experience_years', '>=', $minYears);
        
        if ($maxYears) {
            $query->where('experience_years', '<=', $maxYears);
        }
        
        return $query;
    }

    /**
     * Scope a query to filter by consultation fee range.
     */
    public function scopeByConsultationFee($query, $minFee, $maxFee = null)
    {
        $query->where('consultation_fee', '>=', $minFee);
        
        if ($maxFee) {
            $query->where('consultation_fee', '<=', $maxFee);
        }
        
        return $query;
    }

    /**
     * Scope a query to order by rating (highest first).
     */
    public function scopeOrderByRating($query)
    {
        return $query->orderBy('average_rating', 'desc');
    }

    /**
     * Scope a query to order by experience (most experienced first).
     */
    public function scopeOrderByExperience($query)
    {
        return $query->orderBy('experience_years', 'desc');
    }

    /**
     * Get the success rate percentage.
     */
    public function getSuccessRateAttribute()
    {
        if ($this->total_cases_handled == 0) {
            return 0;
        }
        
        return round(($this->cases_won / $this->total_cases_handled) * 100, 2);
    }

    /**
     * Get the profile photo URL.
     */
    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo) {
            return Storage::disk('public')->url($this->profile_photo);
        }
        
        return null;
    }

    /**
     * Get the enrollment certificate URL.
     */
    public function getEnrollmentCertificateUrlAttribute()
    {
        if ($this->enrollment_certificate) {
            return Storage::disk('public')->url($this->enrollment_certificate);
        }
        
        return null;
    }

    /**
     * Get the CoP certificate URL.
     */
    public function getCopCertificateUrlAttribute()
    {
        if ($this->cop_certificate) {
            return Storage::disk('public')->url($this->cop_certificate);
        }
        
        return null;
    }

    /**
     * Get the address proof URL.
     */
    public function getAddressProofUrlAttribute()
    {
        if ($this->address_proof) {
            return Storage::disk('public')->url($this->address_proof);
        }
        
        return null;
    }

    /**
     * Check if lawyer is verified.
     */
    public function isVerified()
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Check if lawyer is pending verification.
     */
    public function isPending()
    {
        return $this->verification_status === 'pending';
    }

    /**
     * Check if lawyer verification is rejected.
     */
    public function isRejected()
    {
        return $this->verification_status === 'rejected';
    }

    /**
     * Mark lawyer as verified.
     */
    public function markAsVerified($verifierId = null, $notes = null)
    {
        $this->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $verifierId,
            'verification_notes' => $notes,
        ]);
    }

    /**
     * Mark lawyer as rejected.
     */
    public function markAsRejected($notes = null)
    {
        $this->update([
            'verification_status' => 'rejected',
            'verification_notes' => $notes,
        ]);
    }

    /**
     * Update lawyer's rating.
     */
    public function updateRating($newRating)
    {
        $totalReviews = $this->total_reviews;
        $currentRating = $this->average_rating;
        
        $newAverageRating = (($currentRating * $totalReviews) + $newRating) / ($totalReviews + 1);
        
        $this->update([
            'average_rating' => round($newAverageRating, 2),
            'total_reviews' => $totalReviews + 1,
        ]);
    }

    /**
     * Increment cases handled.
     */
    public function incrementCasesHandled($won = false)
    {
        $this->increment('total_cases_handled');
        
        if ($won) {
            $this->increment('cases_won');
        }
    }

    // ============================ Laywer Additional Methods ============================
    // Add this relationship method to your existing User model (App\Models\User.php)

    /**
     * Get the lawyer additional details for the user.
     */
    public function lawyerDetails()
    {
        return $this->hasOne(LawyerAdditional::class);
    }

    /**
     * Check if user is a lawyer.
     */
    public function isLawyer(): bool
    {
        return $this->user_type === 2;
    }

    /**
     * Check if user is a verified lawyer.
     */
    public function isVerifiedLawyer(): bool
    {
        return $this->isLawyer() && 
            $this->lawyerDetails && 
            $this->lawyerDetails->isVerified();
    }

    /**
     * Get lawyer's practice areas.
     */
    public function getPracticeAreasAttribute()
    {
        return $this->lawyerDetails ? $this->lawyerDetails->practice_areas : [];
    }

    /**
     * Get lawyer's average rating.
     */
    public function getLawyerRatingAttribute()
    {
        return $this->lawyerDetails ? $this->lawyerDetails->average_rating : 0;
    }

    /**
     * Get lawyer's consultation fee.
     */
    public function getConsultationFeeAttribute()
    {
        return $this->lawyerDetails ? $this->lawyerDetails->consultation_fee : 0;
    }

    /**
     * Get lawyer's experience years.
     */
    public function getExperienceYearsAttribute()
    {
        return $this->lawyerDetails ? $this->lawyerDetails->experience_years : 0;
    }

}
