<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Grade;
use App\Models\Group;
use App\Models\Period;
use Illuminate\Support\Collection;

/**
 * TrendAnalyzer — Detects grade trend direction across the last N grading entries.
 *
 * Algorithm:
 *  1. Collect the last 3 category-weighted averages (ordered by date).
 *  2. Compute slope via linear regression (least squares).
 *  3. Classify:
 *      slope ≤ −5  → significant_decline (high severity)
 *      slope ≤ −2  → moderate_decline    (medium severity)
 *      slope ≥  2  → improving           (low / positive)
 *      else        → stable              (no insight emitted)
 *
 * Only produces an InsightResult when a decline is detected.
 */
final class TrendAnalyzer
{
    /** Minimum data points required for trend analysis. */
    private const MIN_DATA_POINTS = 3;

    /**
     * Analyze trend for a single student in a group/period.
     *
     * @return InsightResult|null Null if no decline detected or insufficient data.
     */
    public function analyze(int $studentId, int $groupId, int $periodId, string $studentName, string $groupSlug): ?InsightResult
    {
        $averages = $this->collectRecentAverages($studentId, $groupId, $periodId);

        if ($averages->count() < self::MIN_DATA_POINTS) {
            return null;
        }

        $slope = $this->calculateSlope($averages->values()->all());

        return match (true) {
            $slope <= -5.0 => new InsightResult(
                type: InsightResult::TYPE_TREND,
                severity: InsightResult::SEVERITY_HIGH,
                message: "{$studentName}: ".__('insights.trend_significant_decline'),
                suggestedAction: __('insights.action_urgent_intervention'),
                route: route('groups.show', $groupSlug),
                meta: ['slope' => round($slope, 2), 'averages' => $averages->values()->all()],
            ),
            $slope <= -2.0 => new InsightResult(
                type: InsightResult::TYPE_TREND,
                severity: InsightResult::SEVERITY_MEDIUM,
                message: "{$studentName}: ".__('insights.trend_moderate_decline'),
                suggestedAction: __('insights.action_monitor_closely'),
                route: route('groups.show', $groupSlug),
                meta: ['slope' => round($slope, 2), 'averages' => $averages->values()->all()],
            ),
            default => null, // stable or improving — no insight needed
        };
    }

    /**
     * Collect the last N grade averages for the student, grouped by date chunks.
     *
     * Uses the most recent grades partitioned into 3 equal-ish buckets
     * to simulate "last 3 grading snapshots."
     *
     * @return Collection<int, float>
     */
    private function collectRecentAverages(int $studentId, int $groupId, int $periodId): Collection
    {
        $grades = Grade::where('student_id', $studentId)
            ->where('group_id', $groupId)
            ->where('period_id', $periodId)
            ->where('max_score', '>', 0)
            ->orderBy('date')
            ->get(['score', 'max_score', 'date']);

        if ($grades->count() < self::MIN_DATA_POINTS) {
            return collect();
        }

        // Split into 3 chronological groups
        $chunks = $grades->split(self::MIN_DATA_POINTS);

        return $chunks->map(function (Collection $chunk): float {
            $totalScore = 0.0;
            $totalMax = 0.0;

            foreach ($chunk as $grade) {
                $totalScore += (float) $grade->score;
                $totalMax += (float) $grade->max_score;
            }

            return $totalMax > 0 ? round(($totalScore / $totalMax) * 100, 2) : 0.0;
        });
    }

    /**
     * Simple linear regression slope (least squares).
     *
     * @param  float[]  $values  Sequential data points.
     */
    private function calculateSlope(array $values): float
    {
        $n = count($values);

        if ($n < 2) {
            return 0.0;
        }

        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumX2 = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $x = (float) $i;
            $y = $values[$i];

            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);

        if ($denominator == 0) {
            return 0.0;
        }

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }
}
