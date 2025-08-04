<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
    ];

    /**
     * Relationship: Notification belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    /**
     * Scope to get only read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        $this->update(['status' => 'read']);
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): void
    {
        $this->update(['status' => 'unread']);
    }
}
