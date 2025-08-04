<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawyerCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'lawyer_id'
    ];

    /**
     * Get lawyers in this category
     */
    public function lawyers(): HasMany
    {
        return $this->hasMany(Lawyer::class, 'specialization', 'category_name');
    }

    /**
     * If lawyer_id is a reference to a specific lawyer
     */
    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    /**
     * Scope for searching categories
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('category_name', 'LIKE', "%{$term}%");
    }
    
    /**
     * Get cases in this category
     */
    public function cases(): HasMany
    {
        return $this->hasMany(LawyerCase::class, 'category_id');
    }
}