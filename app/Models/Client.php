<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Client Model
 *
 * Represents the relationship between a User (client), a Lawyer, and
 * optionally a LawyerService. Each record tracks the lifecycle of a
 * client engagement from `pending` to `closed`.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $lawyer_id
 * @property int|null    $service_id
 * @property string      $status       pending|active|inactive|closed|suspended
 * @property string|null $priority     low|normal|high|urgent (nullable)
 * @property string|null $notes
 * @property \Carbon\Carbon|null $onboarded_at
 * @property \Carbon\Carbon|null $closed_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Client extends Model
{
    use HasFactory, SoftDeletes;

    // ── Table name (explicit for clarity) ────────────────────────────────────
    protected $table = 'clients';

    // ── Mass-assignable columns ───────────────────────────────────────────────
    protected $fillable = [
        'user_id',
        'lawyer_id',
        'service_id',
        'status',
        'priority',   // nullable — user chose to keep it optional
        'notes',
        'onboarded_at',
        'closed_at',
    ];

    // ── Type casting ───────────────────────────────────────────────────────────
    protected function casts(): array
    {
        return [
            'user_id'      => 'integer',
            'lawyer_id'    => 'integer',
            'service_id'   => 'integer',
            'onboarded_at' => 'datetime',
            'closed_at'    => 'datetime',
        ];
    }

    // ── Status Constants ───────────────────────────────────────────────────────

    /** Client has been added but not yet confirmed by lawyer */
    const STATUS_PENDING    = 'pending';

    /** Client is actively being served */
    const STATUS_ACTIVE     = 'active';

    /** Client is temporarily inactive */
    const STATUS_INACTIVE   = 'inactive';

    /** Engagement has been formally closed */
    const STATUS_CLOSED     = 'closed';

    /** Client has been suspended (e.g., non-payment or compliance issue) */
    const STATUS_SUSPENDED  = 'suspended';

    // ── Priority Constants ────────────────────────────────────────────────────

    const PRIORITY_LOW    = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH   = 'high';
    const PRIORITY_URGENT = 'urgent';

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * The user who is the client.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The lawyer managing this client.
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    /**
     * The specific service being availed (optional).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(LawyerService::class, 'service_id');
    }

    // ── Query Scopes ──────────────────────────────────────────────────────────

    /**
     * Scope: only active clients
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: only pending clients
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: filter by a specific status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: filter by a specific priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: all clients of a specific lawyer
     */
    public function scopeForLawyer($query, int $lawyerId)
    {
        return $query->where('lawyer_id', $lawyerId);
    }

    /**
     * Scope: all client records of a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: all clients using a specific service
     */
    public function scopeForService($query, int $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    // ── Status Helper Methods ─────────────────────────────────────────────────

    /**
     * Mark this client as active and set onboarded_at timestamp if not already set.
     */
    public function markAsActive(): bool
    {
        return $this->update([
            'status'       => self::STATUS_ACTIVE,
            'onboarded_at' => $this->onboarded_at ?? now(),
        ]);
    }

    /**
     * Mark this client as closed and record the closed_at timestamp.
     */
    public function markAsClosed(): bool
    {
        return $this->update([
            'status'    => self::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
    }

    /**
     * Mark this client as suspended.
     */
    public function markAsSuspended(): bool
    {
        return $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    /**
     * Mark this client as inactive.
     */
    public function markAsInactive(): bool
    {
        return $this->update(['status' => self::STATUS_INACTIVE]);
    }

    // ── Static Helpers ────────────────────────────────────────────────────────

    /**
     * Return all valid status values.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_CLOSED,
            self::STATUS_SUSPENDED,
        ];
    }

    /**
     * Return all valid priority values.
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_NORMAL,
            self::PRIORITY_HIGH,
            self::PRIORITY_URGENT,
        ];
    }
}
