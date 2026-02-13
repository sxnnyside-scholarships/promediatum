<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Student extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'slug',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function generateSlug(string $firstName, string $lastName): string
    {
        $slug = Str::slug("{$lastName} {$firstName}");
        $original = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }

    // ── Accessors ──

    public function getFullNameAttribute(): string
    {
        return "{$this->last_name}, {$this->first_name}";
    }

    protected $appends = ['full_name'];

    // ── Relationships ──

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_student')
                    ->withPivot('period_id')
                    ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }
}
