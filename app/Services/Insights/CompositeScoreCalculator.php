<?php

declare(strict_types=1);

namespace App\Services\Insights;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\GradeCategory;
use App\Models\Observation;
use App\Services\AcademicService;

/**
 * CompositeScoreCalculator — Produces a normalized 0–100 risk score
 * from multiple academic and behavioral factors.
 *
 * Formula:
 *   risk = (100 − weighted_avg) × 0.40    [Academic weight]
 *        + (100 − attendance_rate) × 0.25  [Attendance weight]
 *        + min(streak × 15, 100) × 0.15    [Consecutive absences weight]
 *        + min(obs_count × 20, 100) × 0.20 [Unresolved observations weight]
 *
 * Each factor is normalized to 0–100 independently, then combined.
 * A score of 0 = no risk, 100 = maximum risk.
 */
final class CompositeScoreCalculator
{
    /** Weight distribution — must sum to 1.0 */
    private const W_ACADEMIC   = 0.40;
    private const W_ATTENDANCE = 0.25;
    private const W_ABSENCES   = 0.15;
    private const W_OBSERVATIONS = 0.20;

    public function __construct(
        private readonly AcademicService $academic,
    ) {}

    /**
     * Calculate composite risk score for a single student in context.
     *
     * @return array{score: float, severity: string, factors: array}
     */
    public function calculate(int $studentId, int $groupId, int $periodId): array
    {
        $average        = $this->academic->calculateWeightedAverage($studentId, $groupId, $periodId);
        $attendanceStats = $this->academic->getAttendanceStats($studentId, $groupId, $periodId);
        $absenceStreak  = $this->academic->detectConsecutiveAbsences($studentId, $groupId, $periodId);
        $unresolvedObs  = Observation::where('student_id', $studentId)
            ->where('group_id', $groupId)
            ->where('period_id', $periodId)
            ->where('status', 'pending')
            ->count();

        // ── Normalize each factor to 0–100 ──

        // Academic: lower average = higher risk
        $academicFactor = $average !== null
            ? max(0.0, min(100.0, 100.0 - $average))
            : 50.0; // No data = moderate risk

        // Attendance: lower rate = higher risk
        $attendanceRate = $attendanceStats['rate'];
        $attendanceFactor = $attendanceRate !== null
            ? max(0.0, min(100.0, 100.0 - $attendanceRate))
            : 0.0; // No records = no risk from attendance

        // Consecutive absences: each absence adds 15 points, capped at 100
        $absenceFactor = min($absenceStreak * 15.0, 100.0);

        // Observations: each unresolved adds 20 points, capped at 100
        $observationFactor = min($unresolvedObs * 20.0, 100.0);

        // ── Compose final score ──
        $score = round(
            ($academicFactor * self::W_ACADEMIC)
            + ($attendanceFactor * self::W_ATTENDANCE)
            + ($absenceFactor * self::W_ABSENCES)
            + ($observationFactor * self::W_OBSERVATIONS),
            1
        );

        $score = max(0.0, min(100.0, $score));

        return [
            'score'    => $score,
            'severity' => self::classifySeverity($score),
            'factors'  => [
                'academic'     => round($academicFactor, 1),
                'attendance'   => round($attendanceFactor, 1),
                'absences'     => round($absenceFactor, 1),
                'observations' => round($observationFactor, 1),
            ],
        ];
    }

    /**
     * Classify a 0–100 score into severity levels.
     */
    public static function classifySeverity(float $score): string
    {
        return match (true) {
            $score >= 76 => InsightResult::SEVERITY_CRITICAL,
            $score >= 51 => InsightResult::SEVERITY_HIGH,
            $score >= 26 => InsightResult::SEVERITY_MEDIUM,
            default      => InsightResult::SEVERITY_LOW,
        };
    }
}
