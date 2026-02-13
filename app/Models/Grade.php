<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    protected $fillable = [
        'student_id',
        'group_id',
        'period_id',
        'category_id',
        'title',
        'score',
        'max_score',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'date' => 'date',
        ];
    }

    /**
     * Normalized score as percentage (0–100).
     */
    public function getPercentageAttribute(): float
    {
        if ($this->max_score == 0) return 0;
        return round(($this->score / $this->max_score) * 100, 2);
    }

    protected $appends = ['percentage'];

    // ── Relationships ──

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GradeCategory::class, 'category_id');
    }
}
