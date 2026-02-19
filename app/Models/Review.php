<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lawyer_id',
        'rating',
        'comment',
        'ip_address',
        'device_id',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'user_id' => 'integer',
            'lawyer_id' => 'integer',
        ];
    }

    /**
     * Get the user who wrote the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lawyer being reviewed
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    /**
     * Scope for rating filter
     */
    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope for recent reviews
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Rating constants
     */
    const RATING_EXCELLENT = 5;
    const RATING_GOOD = 4;
    const RATING_AVERAGE = 3;
    const RATING_POOR = 2;
    const RATING_TERRIBLE = 1;

    /**
     * Get rating text
     */
    public function getRatingTextAttribute(): string
    {
        return match($this->rating) {
            5 => 'Excellent',
            4 => 'Good',
            3 => 'Average',
            2 => 'Poor',
            1 => 'Terrible',
            default => 'Unknown'
        };
    }
}