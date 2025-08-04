<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawyerCase extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lawyers_cases';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'lawyer_id',
        'casename',
        'category_id',
    ];

    /**
     * Get the user that owns the case.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lawyer that handles the case.
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    /**
     * Get the category of the case.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(LawyerCategory::class, 'category_id');
    }
}