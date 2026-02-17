<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_id
 * @property int $group_id
 * @property int $period_id
 * @property int $category_id
 * @property string $title
 * @property float $score
 * @property float $max_score
 * @property \Illuminate\Support\Carbon|null $date
 * @property float $percentage
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
            'score' => 'float',
            'max_score' => 'float',
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
