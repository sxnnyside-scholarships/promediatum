<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Attendance;
use App\Services\AcademicService;

/**
 * AttendanceAnalyzer — Generates insights from attendance patterns.
 *
 * Checks:
 *  - Attendance rate below threshold (< 80%)
 *  - Consecutive absence streaks (≥ 3 days)
 */
final class AttendanceAnalyzer
{
    /** Minimum attendance rate before generating an insight. */
    private const RATE_THRESHOLD = 80.0;

    /** Minimum consecutive absences before alerting. */
    private const STREAK_THRESHOLD = 3;

    public function __construct(
        private readonly AcademicService $academic,
    ) {}

    /**
     * Analyze a student's attendance and return any insights.
     *
     * @return InsightResult[]
     */
    public function analyze(int $studentId, int $groupId, int $periodId, string $studentName, string $groupSlug): array
    {
        $insights = [];

        $stats  = $this->academic->getAttendanceStats($studentId, $groupId, $periodId);
        $streak = $this->academic->detectConsecutiveAbsences($studentId, $groupId, $periodId);

        // Low attendance rate
        if ($stats['rate'] !== null && $stats['rate'] < self::RATE_THRESHOLD) {
            $severity = $stats['rate'] < 60
                ? InsightResult::SEVERITY_HIGH
                : InsightResult::SEVERITY_MEDIUM;

            $insights[] = new InsightResult(
                type:            InsightResult::TYPE_ATTENDANCE,
                severity:        $severity,
                message:         "{$studentName}: " . __('insights.low_attendance', ['rate' => $stats['rate']]),
                suggestedAction: __('insights.action_review_attendance'),
                route:           route('attendance.index', $groupSlug),
                meta:            [
                    'student_id'      => $studentId,
                    'attendance_rate'  => $stats['rate'],
                    'absent'           => $stats['absent'],
                    'total'            => $stats['total'],
                ],
            );
        }

        // Consecutive absence streak
        if ($streak >= self::STREAK_THRESHOLD) {
            $severity = $streak >= 5
                ? InsightResult::SEVERITY_CRITICAL
                : InsightResult::SEVERITY_HIGH;

            $insights[] = new InsightResult(
                type:            InsightResult::TYPE_ATTENDANCE,
                severity:        $severity,
                message:         "{$studentName}: " . __('insights.consecutive_absences', ['count' => $streak]),
                suggestedAction: __('insights.action_contact_guardian'),
                route:           route('attendance.index', $groupSlug),
                meta:            [
                    'student_id'     => $studentId,
                    'absence_streak' => $streak,
                ],
            );
        }

        return $insights;
    }
}
