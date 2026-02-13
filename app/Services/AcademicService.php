<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\GradeCategory;
use Illuminate\Support\Collection;

class AcademicService
{
    /**
     * Calculate weighted average for a student in a group/period.
     * Returns null if no grades or no categories.
     */
    public function calculateWeightedAverage(int $studentId, int $groupId, int $periodId): ?float
    {
        $categories = GradeCategory::where('group_id', $groupId)->get();

        if ($categories->isEmpty()) {
            return null;
        }

        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($categories as $category) {
            $grades = Grade::where('student_id', $studentId)
                ->where('group_id', $groupId)
                ->where('period_id', $periodId)
                ->where('category_id', $category->id)
                ->get();

            if ($grades->isEmpty()) {
                continue;
            }

            // Average percentage within this category
            $categoryAvg = $grades->avg(fn ($g) => ($g->score / $g->max_score) * 100);

            $weightedSum += $categoryAvg * ($category->weight / 100);
            $totalWeight += $category->weight;
        }

        if ($totalWeight === 0) {
            return null;
        }

        // Scale to actual weight used (in case not all categories have grades yet)
        return round(($weightedSum / $totalWeight) * 100, 2);
    }

    /**
     * Get attendance stats for a student in a group/period.
     */
    public function getAttendanceStats(int $studentId, int $groupId, int $periodId): array
    {
        $records = Attendance::where('student_id', $studentId)
            ->where('group_id', $groupId)
            ->where('period_id', $periodId)
            ->get();

        $total = $records->count();
        $present = $records->where('status', 'present')->count();
        $absent = $records->where('status', 'absent')->count();
        $justified = $records->where('status', 'justified')->count();

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'justified' => $justified,
            'rate' => $total > 0 ? round(($present / $total) * 100, 1) : null,
        ];
    }

    /**
     * Detect consecutive absences for a student in a group/period.
     * Returns the current streak count.
     */
    public function detectConsecutiveAbsences(int $studentId, int $groupId, int $periodId, int $threshold = 3): int
    {
        $records = Attendance::where('student_id', $studentId)
            ->where('group_id', $groupId)
            ->where('period_id', $periodId)
            ->orderByDesc('date')
            ->get();

        $streak = 0;
        foreach ($records as $record) {
            if ($record->status === 'absent') {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Check if a student is at risk (average below threshold).
     */
    public function isAtRisk(int $studentId, int $groupId, int $periodId, float $threshold = 60): bool
    {
        $average = $this->calculateWeightedAverage($studentId, $groupId, $periodId);
        return $average !== null && $average < $threshold;
    }

    /**
     * Get a summary for a student in a group/period.
     */
    public function getStudentSummary(int $studentId, int $groupId, int $periodId): array
    {
        $average = $this->calculateWeightedAverage($studentId, $groupId, $periodId);
        $attendance = $this->getAttendanceStats($studentId, $groupId, $periodId);
        $absenceStreak = $this->detectConsecutiveAbsences($studentId, $groupId, $periodId);

        return [
            'average' => $average,
            'attendance' => $attendance,
            'absence_streak' => $absenceStreak,
            'has_absence_alert' => $absenceStreak >= 3,
            'at_risk' => $average !== null && $average < 60,
        ];
    }

    /**
     * Get category breakdown for a student in a group/period.
     */
    public function getCategoryBreakdown(int $studentId, int $groupId, int $periodId): Collection
    {
        $categories = GradeCategory::where('group_id', $groupId)
            ->with(['grades' => function ($query) use ($studentId, $periodId) {
                $query->where('student_id', $studentId)
                    ->where('period_id', $periodId)
                    ->orderBy('date');
            }])
            ->get();

        return $categories->map(function ($category) {
            $grades = $category->grades;
            $avg = $grades->isNotEmpty()
                ? round($grades->avg(fn ($g) => ($g->score / $g->max_score) * 100), 2)
                : null;

            return [
                'id' => $category->id,
                'name' => $category->name,
                'weight' => $category->weight,
                'grades' => $grades,
                'average' => $avg,
                'weighted_contribution' => $avg !== null ? round($avg * ($category->weight / 100), 2) : null,
            ];
        });
    }

    /**
     * Validate that category weights for a group don't exceed 100%.
     */
    public function validateCategoryWeights(int $groupId, ?int $excludeId = null): float
    {
        $query = GradeCategory::where('group_id', $groupId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return (float) $query->sum('weight');
    }
}
