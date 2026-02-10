<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lawyer_id',
        'appointment_time',
        'duration_minutes',
        'status',
        'meeting_link',
        'consultation_duration_minutes',
        'consultation_enabled',
        'consultation_join_time',
        'consultation_status',
    ];

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'appointment_time' => 'datetime',
            'duration_minutes' => 'integer',
            'user_id' => 'string',
            'lawyer_id' => 'string',
        ];
    }

    /**
     * Get the user for this appointment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lawyer for this appointment
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    /**
     * Get the consultation session for this appointment
     */
    public function consultationSession()
    {
        return $this->hasOne(ConsultationSession::class);
    }

    /**
     * Check if consultation can be joined (1 minute before appointment time)
     */
    public function canJoinConsultation(): bool
    {
        if (!$this->consultation_enabled) {
            return false;
        }

        $now = now();
        $appointmentTime = $this->appointment_time;
        $endTime = $this->getEndTimeAttribute();

        // Can join 1 minute before scheduled time
        $joinTime = $appointmentTime->copy()->subMinute();

        return $now->greaterThanOrEqualTo($joinTime) && $now->lessThanOrEqualTo($endTime);
    }

    /**
     * Get minutes until can join consultation
     */
    public function getMinutesUntilJoinAttribute(): ?int
    {
        if (!$this->consultation_enabled) {
            return null;
        }

        $joinTime = $this->appointment_time->copy()->subMinute();
        $now = now();

        if ($now->greaterThanOrEqualTo($joinTime)) {
            return 0; // Can join now
        }

        return (int) $now->diffInMinutes($joinTime);
    }

    /**
     * Status constants
     */
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no-show';
    const STATUS_IN_PROGRESS = 'in-progress';

    /**
     * Scope for status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for upcoming appointments
     */
    public function scopeUpcoming($query)
    {
        return $query->where('appointment_time', '>', now())
                    ->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope for today's appointments
     */
    public function scopeToday($query)
    {
        return $query->whereDate('appointment_time', today());
    }

    /**
     * Get end time
     */
    public function getEndTimeAttribute()
    {
        return $this->appointment_time->addMinutes($this->duration_minutes);
    }

    /**
     * Get formatted time range
     */
    public function getTimeRangeAttribute(): string
    {
        return $this->appointment_time->format('g:i A') . ' - ' . $this->end_time->format('g:i A');
    }

    /**
     * Check if appointment is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->appointment_time->isFuture() && $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if appointment is today
     */
    public function isToday(): bool
    {
        return $this->appointment_time->isToday();
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): bool
    {
        return $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED && 
               $this->appointment_time->isFuture();
    }

    /**
     * Can be marked as completed
     */
    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS ||
               ($this->status === self::STATUS_SCHEDULED && $this->appointment_time->isPast());
    }

     public static function getStatuses(): array
    {
        return [
            self::STATUS_SCHEDULED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_NO_SHOW,
            self::STATUS_IN_PROGRESS,
        ];
    }
}