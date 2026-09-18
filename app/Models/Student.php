<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $slug
 * @property string $full_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Group> $groups
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Observation> $observations
 */
class Student extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'slug',
        'email',
        'phone',
        'guardian_name',
        'notes',
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
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute(): string
    {
        $first = mb_substr(trim($this->first_name), 0, 1);
        $last = mb_substr(trim($this->last_name), 0, 1);

        return mb_strtoupper("{$first}{$last}");
    }

    protected $appends = ['full_name', 'initials'];

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
