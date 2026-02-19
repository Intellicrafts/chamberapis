<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentHistory extends Model
{
    protected $table = 'appointments_history';

    protected $fillable = [
        'appointment_id',
        'user_id',
        'lawyer_id',
        'status',
        'lawyer_joined_at',
        'user_joined_at',
        'is_paid',
        'cancellation_reason',
        'appointment_data',
    ];

    protected function casts(): array
    {
        return [
            'lawyer_joined_at'   => 'datetime',
            'user_joined_at'     => 'datetime',
            'is_paid'            => 'boolean',
            'appointment_data'   => 'array',
        ];
    }

    // ── Status constants ─────────────────────────────────────────────────────
    const STATUS_COMPLETED    = 'completed';
    const STATUS_NO_SHOW      = 'no_show';
    const STATUS_LATE         = 'late';
    const STATUS_CANCELLED    = 'cancelled';
    const STATUS_RESCHEDULED  = 'rescheduled';

    /**
     * The original appointment.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * The user who booked.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The lawyer assigned.
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeNoShows($query)
    {
        return $query->where('status', self::STATUS_NO_SHOW);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeForLawyer($query, int $lawyerId)
    {
        return $query->where('lawyer_id', $lawyerId);
    }

    /**
     * Detect whether the lawyer was late (joined more than 5 min after scheduled).
     * Requires `appointment_data` to contain `appointment_time`.
     */
    public function lawyerWasLate(int $thresholdMinutes = 5): bool
    {
        if (! $this->lawyer_joined_at || ! isset($this->appointment_data['appointment_time'])) {
            return false;
        }

        $scheduled = \Carbon\Carbon::parse($this->appointment_data['appointment_time']);
        return $this->lawyer_joined_at->diffInMinutes($scheduled, false) > $thresholdMinutes;
    }
}
