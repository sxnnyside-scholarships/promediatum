<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Period extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generate a unique slug from the name.
     */
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

    /**
     * Activate this period and deactivate all others.
     */
    public function activate(): void
    {
        static::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate this period.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Calculate the number of days remaining until end_date.
     * Returns 0 if the period has already ended.
     */
    public function getDaysRemainingAttribute(): int
    {
        $today = now()->startOfDay();
        $end = $this->end_date->startOfDay();

        if ($end->lt($today)) {
            return 0;
        }

        return (int) $today->diffInDays($end);
    }

    /**
     * Append computed attributes to JSON/array.
     */
    protected $appends = ['days_remaining'];

    /**
     * Get the currently active period.
     */
    public static function active(): ?static
    {
        return static::where('is_active', true)->first();
    }

    // ── Relationships ──

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
