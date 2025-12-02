<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    use HasFactory;

    protected $table = 'sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity'
    ];

    protected function casts(): array
    {
        return [
            'last_activity' => 'integer',
        ];
    }

    protected $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'session_id', 'id');
    }

    public function chatEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'session_id', 'id')
                    ->where('event_type', 'chat')
                    ->orderBy('occurred_at', 'asc');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithConversations($query)
    {
        return $query->whereHas('events', function ($q) {
            $q->where('event_type', 'chat');
        });
    }

    public function getLastActivityDateAttribute()
    {
        return \Carbon\Carbon::createFromTimestamp($this->last_activity);
    }
}
