<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Period;

/**
 * InsightContext — Encapsulates the scope for insight generation.
 *
 * At minimum a period is required. Group and student are optional
 * and narrow the scope of analysis.
 */
final class InsightContext
{
    public function __construct(
        public readonly int  $periodId,
        public readonly ?int $groupId = null,
        public readonly ?int $studentId = null,
    ) {}

    /**
     * Build from the active period (workspace-level context).
     */
    public static function fromActivePeriod(): ?self
    {
        $period = Period::where('is_active', true)->first();

        if ($period === null) {
            return null;
        }

        return new self(periodId: $period->id);
    }

    /**
     * Build from an array (e.g. request input).
     *
     * @param array{period_id: int, group_id?: int, student_id?: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            periodId:  (int) $data['period_id'],
            groupId:   isset($data['group_id']) ? (int) $data['group_id'] : null,
            studentId: isset($data['student_id']) ? (int) $data['student_id'] : null,
        );
    }
}
