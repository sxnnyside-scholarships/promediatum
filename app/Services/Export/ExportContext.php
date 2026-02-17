<?php

namespace App\Services\Export;

/**
 * ExportContext — Immutable value object describing what to export.
 *
 * Encapsulates the export request parameters so every layer
 * (resolver, exporter, manager) works with a typed contract
 * instead of raw arrays.
 */
class ExportContext
{
    public function __construct(
        public readonly string $type,          // group | student | period
        public readonly string $format,        // csv | json | xlsx
        public readonly int    $periodId,
        public readonly ?int   $groupId = null,
        public readonly ?int   $studentId = null,
        public readonly array  $filters = [],
        public readonly ?int   $templateId = null, // reserved for future template engine
    ) {}

    /**
     * Create from a validated request array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type:       $data['type'],
            format:     $data['format'],
            periodId:   (int) $data['period_id'],
            groupId:    isset($data['group_id']) ? (int) $data['group_id'] : null,
            studentId:  isset($data['student_id']) ? (int) $data['student_id'] : null,
            filters:    $data['filters'] ?? [],
            templateId: isset($data['template_id']) ? (int) $data['template_id'] : null,
        );
    }

    /**
     * Generate a human-readable file name.
     */
    public function generateFileName(): string
    {
        $parts = [
            'export',
            $this->type,
            $this->periodId,
        ];

        if ($this->groupId) {
            $parts[] = 'g' . $this->groupId;
        }

        if ($this->studentId) {
            $parts[] = 's' . $this->studentId;
        }

        $parts[] = now()->format('Ymd_His');

        $extension = match ($this->format) {
            'csv'  => 'csv',
            'json' => 'json',
            'xlsx' => 'xlsx',
            'pdf'  => 'pdf',
            default => 'dat',
        };

        return implode('_', $parts) . '.' . $extension;
    }
}
