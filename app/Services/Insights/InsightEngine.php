<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Group;
use Illuminate\Support\Facades\Cache;

/**
 * InsightEngine — Orchestrates all analyzers to produce a ranked list of insights.
 *
 * Entry point for the insights system. Delegates to:
 *   - RiskCalculator (composite risk scores)
 *   - TrendAnalyzer (grade trajectory)
 *   - AttendanceAnalyzer (attendance patterns)
 *   - ObservationAnalyzer (unresolved observations)
 *
 * Results are cached per period to avoid expensive recalculation on every page load.
 * Cache is invalidated when grades, attendance, or observations change.
 */
final class InsightEngine
{
    /** Cache TTL in seconds (15 minutes). */
    private const CACHE_TTL = 900;

    /** Cache key prefix. */
    private const CACHE_PREFIX = 'insights:period:';

    public function __construct(
        private readonly RiskCalculator $riskCalculator,
        private readonly TrendAnalyzer $trendAnalyzer,
        private readonly AttendanceAnalyzer $attendanceAnalyzer,
        private readonly ObservationAnalyzer $observationAnalyzer,
    ) {}

    /**
     * Generate insights for the given context.
     *
     * @param  int  $limit  Maximum insights to return.
     * @return InsightResult[]
     */
    public function generate(InsightContext $context, int $limit = 5): array
    {
        $cacheKey = $this->cacheKey($context);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return array_slice($cached, 0, $limit);
        }

        $insights = $this->compute($context);

        // Sort by severity (highest first)
        usort($insights, fn (InsightResult $a, InsightResult $b): int => $b->severityWeight() <=> $a->severityWeight()
        );

        Cache::put($cacheKey, $insights, self::CACHE_TTL);

        return array_slice($insights, 0, $limit);
    }

    /**
     * Get insights serialized for the frontend.
     *
     * @return array<int, array{type: string, severity: string, message: string, suggested_action: string, route: ?string, meta: ?array}>
     */
    public function forWorkspace(InsightContext $context, int $limit = 5): array
    {
        $insights = $this->generate($context, $limit);

        return array_map(
            fn (InsightResult $insight): array => $insight->toArray(),
            $insights
        );
    }

    /**
     * Invalidate cached insights for a period.
     */
    public static function invalidateCache(int $periodId): void
    {
        Cache::forget(self::CACHE_PREFIX.$periodId);
    }

    /**
     * Run all analyzers and collect insights.
     *
     * @return InsightResult[]
     */
    private function compute(InsightContext $context): array
    {
        $insights = [];

        // Load all groups in this period with their students (eager-loaded to avoid N+1)
        $groupsQuery = Group::where('period_id', $context->periodId)
            ->where('is_archived', false)
            ->with(['students' => function ($query) use ($context) {
                $query->wherePivot('period_id', $context->periodId);
                if ($context->studentId !== null) {
                    $query->where('students.id', $context->studentId);
                }
            }]);

        if ($context->groupId !== null) {
            $groupsQuery->where('id', $context->groupId);
        }

        $groups = $groupsQuery->get();

        foreach ($groups as $group) {
            foreach ($group->students as $student) {
                /** @var \App\Models\Student $student */
                $studentName = $student->full_name;
                $groupSlug = $group->slug;
                $studentId = $student->id;
                $groupId = $group->id;
                $periodId = $context->periodId;

                // Risk analysis
                $riskInsight = $this->riskCalculator->analyze(
                    $studentId, $groupId, $periodId, $studentName, $groupSlug
                );
                if ($riskInsight !== null) {
                    $insights[] = $riskInsight;
                }

                // Trend analysis
                $trendInsight = $this->trendAnalyzer->analyze(
                    $studentId, $groupId, $periodId, $studentName, $groupSlug
                );
                if ($trendInsight !== null) {
                    $insights[] = $trendInsight;
                }

                // Attendance analysis
                $attendanceInsights = $this->attendanceAnalyzer->analyze(
                    $studentId, $groupId, $periodId, $studentName, $groupSlug
                );
                array_push($insights, ...$attendanceInsights);

                // Observation analysis
                $observationInsights = $this->observationAnalyzer->analyze(
                    $studentId, $groupId, $periodId, $studentName, $groupSlug
                );
                array_push($insights, ...$observationInsights);
            }
        }

        return $insights;
    }

    private function cacheKey(InsightContext $context): string
    {
        $key = self::CACHE_PREFIX.$context->periodId;

        if ($context->groupId !== null) {
            $key .= ':group:'.$context->groupId;
        }

        if ($context->studentId !== null) {
            $key .= ':student:'.$context->studentId;
        }

        return $key;
    }
}
