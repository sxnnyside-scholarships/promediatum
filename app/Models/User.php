<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'institution',
        'pronoun',
        'educational_area',
        'educational_level',
        'profile_photo_path',
        'is_locked',
        'locale',
        'settings',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_locked' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the gendered greeting based on pronoun.
     */
    public function getGreetingAttribute(): string
    {
        return match ($this->pronoun) {
            'ella' => "Bienvenida, {$this->first_name}",
            'elle' => "Bienvenide, {$this->first_name}",
            default => "Bienvenido, {$this->first_name}",
        };
    }

    /**
     * Get the localized greeting.
     */
    public function getLocalizedGreetingAttribute(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'en') {
            return "Welcome back, {$this->first_name}";
        }

        return $this->greeting;
    }

    /**
     * Recovery codes relationship.
     */
    public function recoveryCodes(): HasMany
    {
        return $this->hasMany(RecoveryCode::class);
    }

    /**
     * Get only unused recovery codes.
     */
    public function unusedRecoveryCodes(): HasMany
    {
        return $this->recoveryCodes()->where('used', false);
    }

    /**
     * Check if a user already exists (single-user enforcement).
     */
    public static function exists(): bool
    {
        return static::count() > 0;
    }
}
