<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Group extends Model
{
    protected $fillable = [
        'name',
        'subject',
        'educational_level',
        'period_id',
        'slug',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }

    // ── Relationships ──

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'group_student')
                    ->withPivot('period_id')
                    ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function gradeCategories(): HasMany
    {
        return $this->hasMany(GradeCategory::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    // ── Computed ──

    public function getStudentsCountAttribute(): int
    {
        return $this->students()->count();
    }

    protected $appends = ['students_count'];
}
