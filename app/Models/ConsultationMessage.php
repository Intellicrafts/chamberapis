<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'consultation_session_id',
        'sender_id',
        'sender_type',
        'message_type',
        'content',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'is_read',
        'read_at',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    /**
     * Get the consultation session for this message
     */
    public function consultationSession(): BelongsTo
    {
        return $this->belongsTo(ConsultationSession::class);
    }

    /**
     * Get the sender (user or lawyer)
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Mark message as read
     */
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Get file URL if file exists
     */
    public function getFileUrlAttribute(): ?string
    {
        if ($this->file_path) {
            return url('storage/' . $this->file_path);
        }
        return null;
    }

    /**
     * Scope for unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for user messages
     */
    public function scopeFromUser($query)
    {
        return $query->where('sender_type', 'user');
    }

    /**
     * Scope for lawyer messages
     */
    public function scopeFromLawyer($query)
    {
        return $query->where('sender_type', 'lawyer');
    }

    /**
     * Scope for system messages
     */
    public function scopeSystem($query)
    {
        return $query->where('sender_type', 'system');
    }

    /**
     * Create a system message
     */
    public static function createSystemMessage(int $sessionId, string $content, array $metadata = [])
    {
        return static::create([
            'consultation_session_id' => $sessionId,
            'sender_id' => 1, // System user ID
            'sender_type' => 'system',
            'message_type' => 'system',
            'content' => $content,
            'is_read' => true,
            'metadata' => $metadata,
        ]);
    }
}
