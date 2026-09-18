<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Models\Period;

/**
 * AutomationContext — Encapsulates the scope and pre-fetched state
 * required by automation rules to evaluate conditions.
 */
final class AutomationContext
{
    public function __construct(
        public readonly int $periodId,
        public readonly ?int $groupId = null,
        public readonly ?int $studentId = null,
        public readonly ?int $daysRemaining = null,
        public readonly int $pendingObservations = 0,
    ) {}

    /**
     * Build from the active period with pre-computed state.
     */
    public static function fromActivePeriod(?Period $period, int $pendingObservations = 0): ?self
    {
        if ($period === null) {
            return null;
        }

        $daysRemaining = null;
        if ($period->end_date instanceof \Carbon\Carbon) {
            $daysRemaining = (int) max(0, now()->diffInDays($period->end_date, false));
        }

        return new self(
            periodId: $period->id,
            daysRemaining: $daysRemaining,
            pendingObservations: $pendingObservations,
        );
    }
}
