<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecializationScore extends Model
{
    protected $table = 'specialization_scores';

    /**
     * specialization_scores uses only `last_updated` (no standard updated_at).
     */
    public $timestamps = false;

    protected $fillable = [
        'lawyer_id',
        'specialization',
        'score',
        'last_updated',
    ];

    protected function casts(): array
    {
        return [
            'score'        => 'decimal:2',
            'last_updated' => 'datetime',
        ];
    }

    /**
     * The lawyer this score belongs to.
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeBySpecialization($query, string $specialization)
    {
        return $query->where('specialization', $specialization);
    }

    public function scopeAboveScore($query, float $score)
    {
        return $query->where('score', '>=', $score);
    }

    public function scopeForLawyer($query, int $lawyerId)
    {
        return $query->where('lawyer_id', $lawyerId);
    }

    /**
     * Update or create a specialization score atomically.
     */
    public static function upsertScore(int $lawyerId, string $specialization, float $score): self
    {
        return self::updateOrCreate(
            ['lawyer_id' => $lawyerId, 'specialization' => $specialization],
            ['score' => $score, 'last_updated' => now()]
        );
    }
}
