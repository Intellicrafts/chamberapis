<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LegalQuery extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_text',
        'ai_response'
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'string',
        ];
    }

    /**
     * Get the user who asked the query
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for recent queries
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for answered queries
     */
    public function scopeAnswered($query)
    {
        return $query->whereNotNull('ai_response');
    }

    /**
     * Scope for unanswered queries
     */
    public function scopeUnanswered($query)
    {
        return $query->whereNull('ai_response');
    }

    /**
     * Scope for search in questions
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('question_text', 'LIKE', "%{$term}%")
                    ->orWhere('ai_response', 'LIKE', "%{$term}%");
    }

    /**
     * Check if query has been answered
     */
    public function isAnswered(): bool
    {
        return !is_null($this->ai_response);
    }

    /**
     * Get truncated question
     */
    public function getTruncatedQuestionAttribute(): string
    {
        return Str::limit($this->question_text, 100);
    }

    /**
     * Get word count of question
     */
    public function getQuestionWordCountAttribute(): int
    {
        return str_word_count($this->question_text);
    }
}