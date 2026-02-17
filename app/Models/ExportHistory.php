<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $format
 * @property int|null $period_id
 * @property int|null $group_id
 * @property int|null $student_id
 * @property int|null $template_id
 * @property string $file_name
 * @property string $file_path
 * @property bool $sent_via_email
 * @property string|null $recipient_email
 * @property string $context_label
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Period|null $period
 * @property-read Group|null $group
 * @property-read Student|null $student
 * @property-read ExportTemplate|null $template
 */
class ExportHistory extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'format',
        'period_id',
        'group_id',
        'student_id',
        'template_id',
        'file_name',
        'file_path',
        'sent_via_email',
        'recipient_email',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ExportTemplate::class, 'template_id');
    }

    // ── Computed ──

    public function getContextLabelAttribute(): string
    {
        $parts = [];

        if ($this->period) {
            $parts[] = $this->period->name;
        }

        if ($this->group) {
            $parts[] = $this->group->name;
        }

        if ($this->student) {
            $parts[] = $this->student->full_name;
        }

        return implode(' → ', $parts);
    }

    protected $appends = ['context_label'];
}
