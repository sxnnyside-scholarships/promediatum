<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'is_default',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'config' => 'array',
        ];
    }

    /**
     * Default config structure for new templates.
     */
    public static function defaultConfig(): array
    {
        return [
            'included_columns' => [],
            'column_order' => [],
            'include_logo' => false,
            'include_header_text' => null,
            'include_footer_text' => null,
            'include_signature_line' => false,
            'date_format' => 'Y-m-d',
            'numeric_precision' => 2,
            'orientation' => 'portrait',
            'include_attendance_summary' => true,
            'include_observations_summary' => true,
            'include_category_breakdown' => true,
        ];
    }

    /**
     * Merge stored config with defaults so every key is guaranteed present.
     */
    public function getNormalizedConfigAttribute(): array
    {
        return array_merge(static::defaultConfig(), $this->config ?? []);
    }

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
