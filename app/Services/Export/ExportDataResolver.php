<?php

namespace App\Services\Export;

use App\Models\Grade;
use App\Models\GradeCategory;
use App\Models\Group;
use App\Models\Observation;
use App\Models\Period;
use App\Models\Student;
use App\Services\AcademicService;

/**
 * ExportDataResolver — Resolves datasets for export according to academic rules.
 *
 * All calculations are contextual: student_id + group_id + period_id.
 * No global averages. Delegates weighted-average math to AcademicService.
 */
class ExportDataResolver
{
    public function __construct(
        protected AcademicService $academic,
    ) {}

    /**
     * Resolve the dataset for the given export context.
     *
     * @return array Structured data ready for the exporter.
     */
    public function resolve(ExportContext $context): array
    {
        return match ($context->type) {
            'group' => $this->resolveGroupExport($context),
            'student' => $this->resolveStudentExport($context),
            'period' => $this->resolvePeriodExport($context),
            default => throw new \InvalidArgumentException("Unknown export type: {$context->type}"),
        };
    }

    // ────────────────────────────────────────────────
    // GROUP EXPORT
    // ────────────────────────────────────────────────

    protected function resolveGroupExport(ExportContext $context): array
    {
        $group = Group::with(['period', 'gradeCategories'])->findOrFail($context->groupId);
        /** @var Period $period */
        $period = $group->period;

        /** @var \Illuminate\Database\Eloquent\Collection<int, Student> $students */
        $students = $group->students()
            ->wherePivot('period_id', $context->periodId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $studentSummaries = $this->academic->getGroupStudentSummaries(
            $students->pluck('id')->all(),
            $group->id,
            $context->periodId
        );

        $studentRows = $students->map(function (Student $student) use ($studentSummaries) {
            $summary = $studentSummaries[$student->id] ?? [
                'average' => null,
                'attendance' => [
                    'total' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'justified' => 0,
                    'rate' => null,
                ],
                'absence_streak' => 0,
                'has_absence_alert' => false,
                'at_risk' => false,
            ];

            return [
                'student_id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'full_name' => $student->full_name,
                'weighted_average' => $summary['average'],
                'attendance_rate' => $summary['attendance']['rate'],
                'present' => $summary['attendance']['present'],
                'absent' => $summary['attendance']['absent'],
                'justified' => $summary['attendance']['justified'],
                'total_sessions' => $summary['attendance']['total'],
                'at_risk' => $summary['at_risk'],
                'absence_streak' => $summary['absence_streak'],
            ];
        });

        $categories = $group->gradeCategories->map(fn (GradeCategory $cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'weight' => (float) $cat->weight,
        ]);

        return [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'subject' => $group->subject,
                'educational_level' => $group->educational_level,
            ],
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date?->toDateString(),
                'end_date' => $period->end_date?->toDateString(),
            ],
            'categories' => $categories->toArray(),
            'students' => $studentRows->toArray(),
        ];
    }

    // ────────────────────────────────────────────────
    // STUDENT EXPORT
    // ────────────────────────────────────────────────

    protected function resolveStudentExport(ExportContext $context): array
    {
        $student = Student::findOrFail($context->studentId);
        $group = Group::with('period')->findOrFail($context->groupId);
        /** @var Period $period */
        $period = Period::findOrFail($context->periodId);

        $summary = $this->academic->getStudentSummary($student->id, $group->id, $period->id);
        /** @var \Illuminate\Support\Collection $categories */
        $categories = $this->academic->getCategoryBreakdown($student->id, $group->id, $period->id);

        $observations = Observation::where('student_id', $student->id)
            ->where('group_id', $group->id)
            ->where('period_id', $period->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Observation $obs) => [
                'type' => $obs->type,
                'content' => $obs->content,
                'status' => $obs->status,
                'created_at' => $obs->created_at?->toDateTimeString(),
            ])
            ->toArray();

        return [
            'student' => [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'full_name' => $student->full_name,
            ],
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'subject' => $group->subject,
            ],
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date?->toDateString(),
                'end_date' => $period->end_date?->toDateString(),
            ],
            'academic' => [
                'weighted_average' => $summary['average'],
                'at_risk' => $summary['at_risk'],
                'absence_streak' => $summary['absence_streak'],
                'has_absence_alert' => $summary['has_absence_alert'],
            ],
            'attendance' => $summary['attendance'],
            'categories' => $categories->map(fn (array $cat) => [
                'id' => $cat['id'],
                'name' => $cat['name'],
                'weight' => $cat['weight'],
                'average' => $cat['average'],
                'weighted_contribution' => $cat['weighted_contribution'],
                'grades_count' => $cat['grades']->count(),
                'grades' => $cat['grades']->map(fn (Grade $g) => [
                    'title' => $g->title,
                    'score' => (float) $g->score,
                    'max_score' => (float) $g->max_score,
                    'percentage' => $g->percentage,
                    'date' => $g->date instanceof \Carbon\Carbon ? $g->date->toDateString() : (string) $g->date,
                ])->toArray(),
            ])->toArray(),
            'observations' => $observations,
        ];
    }

    // ────────────────────────────────────────────────
    // PERIOD EXPORT
    // ────────────────────────────────────────────────

    protected function resolvePeriodExport(ExportContext $context): array
    {
        $period = Period::findOrFail($context->periodId);

        $groups = Group::where('period_id', $period->id)
            ->with('gradeCategories')
            ->orderBy('name')
            ->get();

        $groupSummaries = $groups->map(function (Group $group) use ($context) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, Student> $students */
            $students = $group->students()
                ->wherePivot('period_id', $context->periodId)
                ->get();

            $averages = [];
            $attendanceRates = [];
            $riskCount = 0;

            foreach ($students as $student) {
                $summary = $this->academic->getStudentSummary($student->id, $group->id, $context->periodId);

                if ($summary['average'] !== null) {
                    $averages[] = $summary['average'];
                }

                if ($summary['attendance']['rate'] !== null) {
                    $attendanceRates[] = $summary['attendance']['rate'];
                }

                if ($summary['at_risk']) {
                    $riskCount++;
                }
            }

            $groupAvg = count($averages) > 0 ? round(array_sum($averages) / count($averages), 2) : null;
            $groupAttendance = count($attendanceRates) > 0
                ? round(array_sum($attendanceRates) / count($attendanceRates), 1)
                : null;

            return [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'subject' => $group->subject,
                'educational_level' => $group->educational_level,
                'students_count' => $students->count(),
                'group_average' => $groupAvg,
                'group_attendance' => $groupAttendance,
                'students_at_risk' => $riskCount,
                'categories_count' => $group->gradeCategories->count(),
                'total_weight' => (float) $group->gradeCategories->sum('weight'),
            ];
        });

        return [
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date?->toDateString(),
                'end_date' => $period->end_date?->toDateString(),
                'is_active' => $period->is_active,
            ],
            'summary' => [
                'total_groups' => $groups->count(),
                'total_students' => $groupSummaries->sum('students_count'),
                'total_at_risk' => $groupSummaries->sum('students_at_risk'),
            ],
            'groups' => $groupSummaries->toArray(),
        ];
    }
}
