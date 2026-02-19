<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceEvent extends Model
{
    protected $table = 'compliance_events';

    /**
     * compliance_events uses a non-standard timestamps layout:
     * only `created_at` (no `updated_at`).  We manage timestamps manually.
     */
    public $timestamps = false;

    protected $fillable = [
        'lawyer_id',
        'event_type',
        'description',
        'occurred_at',
        'created_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'created_at'  => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    // ── Event-type constants ─────────────────────────────────────────────────
    const TYPE_MINOR_COMPLAINT       = 'minor_complaint';
    const TYPE_VERIFIED_MISCONDUCT   = 'verified_misconduct';
    const TYPE_REFUND_DISPUTE_LOSS   = 'refund_dispute_loss';

    /**
     * The lawyer linked to this event.
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeForLawyer($query, int $lawyerId)
    {
        return $query->where('lawyer_id', $lawyerId);
    }

    /**
     * Mark this event as resolved right now.
     */
    public function resolve(): bool
    {
        $this->resolved_at = now();
        return $this->save();
    }
}
