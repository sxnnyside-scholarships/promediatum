<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmtpSetting extends Model
{
    protected $fillable = [
        'user_id',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_name',
        'from_email',
        'verified',
    ];

    protected function casts(): array
    {
        return [
            'password'  => 'encrypted',
            'port'      => 'integer',
            'verified'  => 'boolean',
        ];
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ──

    /**
     * Check whether the minimal required fields are present.
     */
    public function isConfigured(): bool
    {
        return $this->host
            && $this->port
            && $this->username
            && $this->password
            && $this->from_email;
    }
}
