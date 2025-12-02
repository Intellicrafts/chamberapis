<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'session_id',
        'user_id',
        'event_type',
        'event_name',
        'event_data',
        'ip_address',
        'user_agent',
        'url',
        'referrer',
        'occurred_at'
    ];

    protected function casts(): array
    {
        return [
            'event_data' => 'json',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'session_id', 'id');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeChatEvents($query)
    {
        return $query->where('event_type', 'chat');
    }

    public function scopeOrderByOccurrence($query)
    {
        return $query->orderBy('occurred_at', 'asc');
    }
}
