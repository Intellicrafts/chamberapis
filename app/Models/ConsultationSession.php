<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class ConsultationSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['client_name'];

    protected $fillable = [
        'appointment_id',
        'user_id',
        'lawyer_id',
        'session_token',
        'status',
        'scheduled_start_time',
        'actual_start_time',
        'scheduled_end_time',
        'actual_end_time',
        'duration_minutes',
        'user_joined_at',
        'lawyer_joined_at',
        'ended_by',
        'end_reason',
        'metadata',
    ];

    protected $casts = [
        'scheduled_start_time' => 'datetime',
        'actual_start_time' => 'datetime',
        'scheduled_end_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'user_joined_at' => 'datetime',
        'lawyer_joined_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get client name (User's name)
     */
    public function getClientNameAttribute()
    {
        return $this->user ? $this->user->name : 'N/A';
    }

    /**
     * Boot method to generate session token
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (!$session->session_token) {
                $session->session_token = Str::uuid();
            }
        });
    }

    /**
     * Get the appointment for this session
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the user (client) for this session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the lawyer for this session
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class, 'lawyer_id');
    }

    /**
     * Get the user who ended the session
     */
    public function endedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    /**
     * Get all messages for this session
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConsultationMessage::class);
    }

    /**
     * Get analytics for this session
     */
    public function analytics(): HasOne
    {
        return $this->hasOne(ConsultationAnalytics::class);
    }

    /**
     * Check if session can be joined (within time window)
     */
    public function canBeJoined(): bool
    {
        if ($this->status === 'completed' || $this->status === 'expired' || $this->status === 'cancelled') {
            return false;
        }

        $now = now();
        $scheduledStart = $this->scheduled_start_time;
        $scheduledEnd = $this->scheduled_end_time;

        // Can join 1 minute before scheduled time
        $joinWindow = $scheduledStart->copy()->subMinute();

        return $now->greaterThanOrEqualTo($joinWindow) && $now->lessThanOrEqualTo($scheduledEnd);
    }

    /**
     * Check if session is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && now()->lessThan($this->scheduled_end_time);
    }

    /**
     * Check if session has expired
     */
    public function hasExpired(): bool
    {
        return now()->greaterThan($this->scheduled_end_time);
    }

    public function isParticipant(int $userId): bool
    {
        if ($this->user_id === $userId) return true;
        
        $user = \App\Models\User::find($userId);
        if ($user && $user->lawyer && $user->lawyer->id === $this->lawyer_id) {
            return true;
        }

        return $this->lawyer_id === $userId;
    }

    /**
     * Mark user as joined
     */
    public function markUserJoined()
    {
        $this->update([
            'user_joined_at' => now(),
            'status' => $this->lawyer_joined_at ? 'active' : 'waiting',
        ]);

        if (!$this->actual_start_time && $this->lawyer_joined_at) {
            $this->update(['actual_start_time' => now()]);
        }
    }

    /**
     * Mark lawyer as joined
     */
    public function markLawyerJoined()
    {
        $this->update([
            'lawyer_joined_at' => now(),
            'status' => $this->user_joined_at ? 'active' : 'waiting',
        ]);

        if (!$this->actual_start_time && $this->user_joined_at) {
            $this->update(['actual_start_time' => now()]);
        }
    }

    /**
     * End the session
     */
    public function endSession(int $endedByUserId, string $reason = 'completed')
    {
        $this->update([
            'status' => 'completed',
            'actual_end_time' => now(),
            'ended_by' => $endedByUserId,
            'end_reason' => $reason,
        ]);

        // Update appointment status
        if ($this->appointment) {
            $this->appointment->update([
                'consultation_status' => 'completed',
            ]);
        }

        // Create or update analytics
        $this->createAnalytics();
    }

    /**
     * Create analytics record for the session
     */
    protected function createAnalytics()
    {
        $messageCount = $this->messages()->count();
        $userMessageCount = $this->messages()->where('sender_type', 'user')->count();
        $lawyerMessageCount = $this->messages()->where('sender_type', 'lawyer')->count();

        $firstMessage = $this->messages()->oldest()->first();
        $lastMessage = $this->messages()->latest()->first();

        $actualDuration = $this->actual_start_time && $this->actual_end_time
            ? $this->actual_start_time->diffInMinutes($this->actual_end_time)
            : $this->duration_minutes;

        ConsultationAnalytics::updateOrCreate(
            ['consultation_session_id' => $this->id],
            [
                'appointment_id' => $this->appointment_id,
                'user_id' => $this->user_id,
                'lawyer_id' => $this->lawyer_id,
                'consultation_date' => $this->scheduled_start_time->toDateString(),
                'duration_minutes' => $actualDuration,
                'message_count' => $messageCount,
                'user_message_count' => $userMessageCount,
                'lawyer_message_count' => $lawyerMessageCount,
                'first_message_at' => $firstMessage?->created_at,
                'last_message_at' => $lastMessage?->created_at,
                'completed_successfully' => $this->end_reason === 'completed',
            ]
        );
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for sessions that can be joined
     */
    public function scopeJoinable($query)
    {
        $now = now();
        $oneMinuteBefore = $now->copy()->addMinute();

        return $query->whereIn('status', ['waiting', 'active'])
            ->where('scheduled_start_time', '<=', $oneMinuteBefore)
            ->where('scheduled_end_time', '>=', $now);
    }

    public function scopeForUser($query, int $userId)
    {
        $user = \App\Models\User::find($userId);
        $lawyerId = $user && $user->lawyer ? $user->lawyer->id : null;

        return $query->where(function ($q) use ($userId, $lawyerId) {
            $q->where('user_id', $userId);
            if ($lawyerId) {
                $q->orWhere('lawyer_id', $lawyerId);
            }
            $q->orWhere('lawyer_id', $userId);
        });
    }
}
