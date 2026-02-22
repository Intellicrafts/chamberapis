<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\User;

class Lawyer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'phone_number',
        'password_hash',
        'active',
        'is_verified',
        'enrollment_no',
        'status',
        'bar_association',
        'specialization',
        'years_of_experience',
        'bio',
        'profile_picture_url',
        'consultation_fee',
        'deleted'
    ];

    protected $hidden = [
        'password_hash',
    ];
    
    /**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $appends = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_verified' => 'boolean',
            'deleted' => 'boolean',
            'years_of_experience' => 'integer',
            'consultation_fee' => 'decimal:2',
        ];
    }

    // No deleted_at column

    /**
     * Get lawyer's appointments
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get lawyer's reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get lawyer's availability slots
     */
    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    /**
     * Get lawyer's categories
     */
    public function categories(): HasMany
    {
        return $this->hasMany(LawyerCategory::class);
    }
    
    /**
     * Get lawyer's cases
     */
    public function cases(): HasMany
    {
        return $this->hasMany(LawyerCase::class);
    }

    /**
     * Scope for active lawyers
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope for verified lawyers
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for specialization
     */
    public function scopeBySpecialization($query, $specialization)
    {
        return $query->where('specialization', $specialization);
    }

    /**
     * Get average rating
     */
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Get total reviews count
     */
    public function getTotalReviewsAttribute()
    {
        return $this->reviews()->count();
    }

    /**
     * Get profile picture URL
     */
    public function getProfilePictureAttribute(): string
    {
        return $this->profile_picture_url 
            ? asset('storage/lawyers/' . $this->profile_picture_url)
            : asset('images/default-lawyer.png');
    }
    
    /**
     * Get name matching User model interface
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }
    
    /**
     * Get available slots for today
     */
    public function getTodayAvailableSlots()
    {
        return $this->availabilitySlots()
            ->where('start_time', '>=', now()->startOfDay())
            ->where('start_time', '<=', now()->endOfDay())
            ->where('is_booked', false)
            ->orderBy('start_time')
            ->get();
    }
    
    /**
     * Get the user associated with this lawyer profile
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}