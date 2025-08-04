<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LegalQuery extends Model
{
    use HasFactory;

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'question_text',
        'ai_response',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the user that owns the legal query.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include queries with AI responses.
     */
    public function scopeWithResponse($query)
    {
        return $query->whereNotNull('ai_response');
    }

    /**
     * Scope a query to only include queries without AI responses.
     */
    public function scopeWithoutResponse($query)
    {
        return $query->whereNull('ai_response');
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to search in question text.
     */
    public function scopeSearchInQuestion($query, $searchTerm)
    {
        return $query->where('question_text', 'LIKE', "%{$searchTerm}%");
    }

    /**
     * Scope a query to search in AI response.
     */
    public function scopeSearchInResponse($query, $searchTerm)
    {
        return $query->where('ai_response', 'LIKE', "%{$searchTerm}%");
    }

    /**
     * Scope a query to search in both question and response.
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('question_text', 'LIKE', "%{$searchTerm}%")
              ->orWhere('ai_response', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Scope a query to get recent queries.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if the query has an AI response.
     */
    public function hasResponse(): bool
    {
        return !is_null($this->ai_response) && !empty(trim($this->ai_response));
    }

    /**
     * Check if the query is pending AI response.
     */
    public function isPending(): bool
    {
        return !$this->hasResponse();
    }

    /**
     * Get the word count of the question.
     */
    public function getQuestionWordCountAttribute(): int
    {
        return str_word_count(strip_tags($this->question_text));
    }

    /**
     * Get the word count of the AI response.
     */
    public function getResponseWordCountAttribute(): int
    {
        if (!$this->hasResponse()) {
            return 0;
        }

        return str_word_count(strip_tags($this->ai_response));
    }

    /**
     * Get the character count of the question.
     */
    public function getQuestionCharCountAttribute(): int
    {
        return strlen($this->question_text);
    }

    /**
     * Get the character count of the AI response.
     */
    public function getResponseCharCountAttribute(): int
    {
        if (!$this->hasResponse()) {
            return 0;
        }

        return strlen($this->ai_response);
    }

    /**
     * Get a truncated version of the question for display.
     */
    public function getTruncatedQuestionAttribute(): string
    {
        return Str::limit($this->question_text, 100);
    }

    /**
     * Get a truncated version of the response for display.
     */
    public function getTruncatedResponseAttribute(): string
    {
        if (!$this->hasResponse()) {
            return 'No response yet';
        }

        return Str::limit($this->ai_response, 100);
    }

    /**
     * Set AI response and update timestamp.
     */
    public function setAiResponse(string $response): bool
    {
        return $this->update([
            'ai_response' => $response,
            'updated_at' => now()
        ]);
    }

    /**
     * Clear AI response.
     */
    public function clearAiResponse(): bool
    {
        return $this->update([
            'ai_response' => null,
            'updated_at' => now()
        ]);
    }

    /**
     * Get the age of the query in human readable format.
     */
    public function getAgeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if the query was created today.
     */
    public function isToday(): bool
    {
        return $this->created_at->isToday();
    }

    /**
     * Check if the query was created this week.
     */
    public function isThisWeek(): bool
    {
        return $this->created_at->isCurrentWeek();
    }

    /**
     * Check if the query was created this month.
     */
    public function isThisMonth(): bool
    {
        return $this->created_at->isCurrentMonth();
    }

    /**
     * Get similar queries based on question text.
     */
    public function getSimilarQueries($limit = 5)
    {
        $keywords = collect(explode(' ', $this->question_text))
            ->filter(function ($word) {
                return strlen($word) > 3; // Filter out short words
            })
            ->take(5); // Take first 5 meaningful words

        $query = static::where('id', '!=', $this->id);

        foreach ($keywords as $keyword) {
            $query->orWhere('question_text', 'LIKE', "%{$keyword}%");
        }

        return $query->limit($limit)->get();
    }

    /**
     * Generate a summary of the query and response.
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'question_preview' => $this->truncated_question,
            'response_preview' => $this->truncated_response,
            'has_response' => $this->hasResponse(),
            'word_count' => [
                'question' => $this->question_word_count,
                'response' => $this->response_word_count,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
            'age' => $this->age,
        ];
    }

    /**
     * Export query data for analysis.
     */
    public function toAnalyticsArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'question_length' => $this->question_char_count,
            'response_length' => $this->response_char_count,
            'has_response' => $this->hasResponse(),
            'created_date' => $this->created_at->toDateString(),
            'created_time' => $this->created_at->toTimeString(),
            'response_time' => $this->hasResponse() ? $this->updated_at->diffInMinutes($this->created_at) : null,
        ];
    }
}