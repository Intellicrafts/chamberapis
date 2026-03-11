<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Added this line

class LawyerService extends Model
{
    protected $fillable = [
        'lawyer_id',
        'service_code',
        'service_name',
        'billing_model',
        'rate',
        'currency',
        'icon',
        'is_active',
        'locked'
    ];

    /**
     * Get the lawyer that owns this service.
     */
    public function lawyer()
    {
        return $this->belongsTo(User::class, 'lawyer_id');
    }
}
