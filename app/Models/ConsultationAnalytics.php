<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_session_id',
        'appointment_id',
        'user_id',
        'lawyer_id',
        'consultation_date',
        'duration_minutes',
        'message_count',
        'user_message_count',
        'lawyer_message_count',
        'first_message_at',
        'last_message_at',
        'response_time_seconds',
        'user_satisfaction',
        'user_feedback',
        'lawyer_notes',
        'consultation_fee',
        'payment_status',
        'connection_issues',
        'completed_successfully',
    ];

    protected $casts = [
        'consultation_date' => 'date',
        'first_message_at' => 'datetime',
        'last_message_at' => 'datetime',
        'completed_successfully' => 'boolean',
        'consultation_fee' => 'decimal:2',
    ];

    /**
     * Get the consultation session
     */
    public function consultationSession(): BelongsTo
    {
        return $this->belongsTo(ConsultationSession::class);
    }

    /**
     * Get the appointment
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the user (client)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the lawyer
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lawyer_id');
    }

    /**
     * Calculate engagement score (0-100)
     */
    public function getEngagementScoreAttribute(): int
    {
        $score = 0;

        // Messages sent (max 40 points)
        $score += min(40, $this->message_count * 2);

        // Duration (max 30 points)
        $score += min(30, ($this->duration_minutes / 55) * 30);

        // Completion (20 points)
        if ($this->completed_successfully) {
            $score += 20;
        }

        // User satisfaction (max 10 points)
        if ($this->user_satisfaction) {
            $score += ($this->user_satisfaction / 5) * 10;
        }

        return (int) $score;
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('consultation_date', [$startDate, $endDate]);
    }

    /**
     * Scope for lawyer's analytics
     */
    public function scopeForLawyer($query, int $lawyerId)
    {
        return $query->where('lawyer_id', $lawyerId);
    }

    /**
     * Scope for user's analytics
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for successful consultations
     */
    public function scopeSuccessful($query)
    {
        return $query->where('completed_successfully', true);
    }
}
