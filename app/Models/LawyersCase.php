<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Lawyer;
use App\Models\LawyerCategory;

class LawyersCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lawyer_id',
        'casename',
        'category_id',
    ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lawyer()
    {
        return $this->belongsTo(Lawyer::class);
    }

    public function category()
    {
        return $this->belongsTo(LawyerCategory::class, 'category_id');
    }
}
