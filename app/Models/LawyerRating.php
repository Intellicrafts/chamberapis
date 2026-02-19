<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawyerRating extends Model
{
    protected $table = 'lawyers_rating';

    protected $fillable = [
        'lawyer_id',
        'rating_score',
        'tier',
        'total_reviews',
        'total_interactions',
    ];

    protected function casts(): array
    {
        return [
            'rating_score'       => 'decimal:2',
            'total_reviews'      => 'integer',
            'total_interactions' => 'integer',
        ];
    }

    // ── Tier constants ───────────────────────────────────────────────────────
    const TIER_PLATINUM = 'Platinum';
    const TIER_GOLD     = 'Gold';
    const TIER_SILVER   = 'Silver';
    const TIER_BRONZE   = 'Bronze';
    const TIER_WAIT     = 'Wait';

    /**
     * The lawyer this rating belongs to.
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    /**
     * Determine the tier from a given score.
     */
    public static function tierFromScore(float $score): string
    {
        return match (true) {
            $score >= 85 => self::TIER_PLATINUM,
            $score >= 70 => self::TIER_GOLD,
            $score >= 50 => self::TIER_SILVER,
            $score >= 30 => self::TIER_BRONZE,
            default      => self::TIER_WAIT,
        };
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    public function scopeAboveScore($query, float $score)
    {
        return $query->where('rating_score', '>=', $score);
    }
}
