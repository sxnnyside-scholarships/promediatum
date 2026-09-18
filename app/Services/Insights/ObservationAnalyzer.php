<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Observation;

/**
 * ObservationAnalyzer — Generates insights from unresolved observations.
 *
 * Checks:
 *  - High count of unresolved observations per student
 *  - Behavior-type observations (higher severity by nature)
 */
final class ObservationAnalyzer
{
    /** Threshold for "many" unresolved observations. */
    private const UNRESOLVED_THRESHOLD = 3;

    /**
     * Analyze observations for a student and return any insights.
     *
     * @return InsightResult[]
     */
    public function analyze(int $studentId, int $groupId, int $periodId, string $studentName, string $groupSlug): array
    {
        $insights = [];

        $unresolved = Observation::where('student_id', $studentId)
            ->where('group_id', $groupId)
            ->where('period_id', $periodId)
            ->where('status', 'pending')
            ->get();

        $count = $unresolved->count();

        if ($count >= self::UNRESOLVED_THRESHOLD) {
            $hasBehavior = $unresolved->contains('type', 'behavior');

            $severity = $hasBehavior
                ? InsightResult::SEVERITY_HIGH
                : InsightResult::SEVERITY_MEDIUM;

            $insights[] = new InsightResult(
                type: InsightResult::TYPE_OBSERVATION,
                severity: $severity,
                message: "{$studentName}: ".__('insights.unresolved_observations', ['count' => $count]),
                suggestedAction: __('insights.action_resolve_observations'),
                route: route('observations.index'),
                meta: [
                    'student_id' => $studentId,
                    'unresolved_count' => $count,
                    'has_behavior' => $hasBehavior,
                ],
            );
        }

        return $insights;
    }
}
