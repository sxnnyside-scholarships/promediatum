<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeCategory extends Model
{
    protected $fillable = [
        'group_id',
        'name',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }

    // ── Relationships ──

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'category_id');
    }
}
