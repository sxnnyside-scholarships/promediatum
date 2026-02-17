<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Observation;

/**
 * RiskCalculator — Generates risk-type insights from composite scores.
 *
 * Delegates score computation to CompositeScoreCalculator,
 * then converts into InsightResult objects.
 */
final class RiskCalculator
{
    public function __construct(
        private readonly CompositeScoreCalculator $scorer,
    ) {}

    /**
     * Analyze risk for a single student and return an InsightResult if at-risk.
     *
     * @return InsightResult|null  Null if student is not at risk (score < 26).
     */
    public function analyze(int $studentId, int $groupId, int $periodId, string $studentName, string $groupSlug): ?InsightResult
    {
        $result = $this->scorer->calculate($studentId, $groupId, $periodId);
        $score  = $result['score'];

        // Only produce insights for medium+ severity
        if ($score < 26) {
            return null;
        }

        $severity = $result['severity'];
        $message  = $this->buildMessage($studentName, $score, $severity);
        $action   = $this->buildAction($severity, $result['factors']);

        return new InsightResult(
            type:            InsightResult::TYPE_RISK,
            severity:        $severity,
            message:         $message,
            suggestedAction: $action,
            route:           route('groups.show', $groupSlug),
            meta:            [
                'student_id' => $studentId,
                'group_id'   => $groupId,
                'score'      => $score,
                'factors'    => $result['factors'],
            ],
        );
    }

    private function buildMessage(string $studentName, float $score, string $severity): string
    {
        $label = match ($severity) {
            InsightResult::SEVERITY_CRITICAL => __('insights.risk_critical'),
            InsightResult::SEVERITY_HIGH     => __('insights.risk_high'),
            default                          => __('insights.risk_medium'),
        };

        return "{$studentName}: {$label} ({$score}/100)";
    }

    private function buildAction(string $severity, array $factors): string
    {
        // Identify the dominant factor
        $dominant = array_keys($factors, max($factors))[0] ?? 'academic';

        return match ($dominant) {
            'academic'     => __('insights.action_review_grades'),
            'attendance'   => __('insights.action_review_attendance'),
            'absences'     => __('insights.action_contact_guardian'),
            'observations' => __('insights.action_resolve_observations'),
            default        => __('insights.action_review_student'),
        };
    }
}
