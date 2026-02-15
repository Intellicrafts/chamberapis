<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AvailabilitySlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'lawyer_id',
        'start_time',
        'end_time',
        'is_booked'
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_booked' => 'boolean',
            'lawyer_id' => 'integer',
        ];
    }

    /**
     * Get the lawyer for this slot
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    /**
     * Scope for available slots
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_booked', false);
    }

    /**
     * Scope for booked slots
     */
    public function scopeBooked($query)
    {
        return $query->where('is_booked', true);
    }

    /**
     * Scope for today's slots
     */
    public function scopeToday($query)
    {
        return $query->whereDate('start_time', Carbon::today());
    }

    /**
     * Scope for future slots
     */
    public function scopeFuture($query)
    {
        return $query->where('start_time', '>', now());
    }

    /**
     * Scope for specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('start_time', $date);
    }

    /**
     * Get duration in minutes
     */
    public function getDurationAttribute(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Get formatted time range
     */
    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('g:i A') . ' - ' . $this->end_time->format('g:i A');
    }

    /**
     * Check if slot is in the past
     */
    public function isPast(): bool
    {
        return $this->start_time->isPast();
    }

    /**
     * Mark slot as booked
     */
    public function markAsBooked(): bool
    {
        return $this->update(['is_booked' => true]);
    }

    /**
     * Mark slot as available
     */
    public function markAsAvailable(): bool
    {
        return $this->update(['is_booked' => false]);
    }
}