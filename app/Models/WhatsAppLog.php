<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppLog extends Model
{
    use HasFactory;

    // Explicitly set table name - Laravel converts 'WhatsAppLog' to 'whats_app_logs' by default
    protected $table = 'whatsapp_logs';

    public $timestamps = false;

    protected $fillable = [
        'phone',
        'message_type',
        'appointment_id',
        'status',
        'twilio_sid',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'appointment_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}

