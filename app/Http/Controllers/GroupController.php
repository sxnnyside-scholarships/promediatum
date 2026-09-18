<?php

namespace App\Http\Controllers;

use App\Models\GradeCategory;
use App\Models\Group;
use App\Models\Period;
use App\Models\Student;
use App\Services\AcademicService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function __construct(
        protected AcademicService $academic
    ) {}

    /**
     * List all groups (scan mode with smart active-period sorting).
     */
    public function index(): Response
    {
        Period::syncAutomaticStatus();
        $activePeriod = Period::active();

        $periods = Period::orderByDesc('start_date')->get(['id', 'name', 'slug', 'is_active']);

        $groups = Group::with('period')
            ->withCount('students')
            ->get()
            ->sort(function (Group $a, Group $b) use ($activePeriod) {
                // Active period priority
                $aIsActive = $activePeriod && $a->period_id === $activePeriod->id;
                $bIsActive = $activePeriod && $b->period_id === $activePeriod->id;

                if ($aIsActive !== $bIsActive) {
                    return $aIsActive ? -1 : 1;
                }

                // Unarchived priority
                if ($a->is_archived !== $b->is_archived) {
                    return $a->is_archived ? 1 : -1;
                }

                return strcasecmp($a->name, $b->name);
            })
            ->values();

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
            'periods' => $periods,
            'activePeriodId' => $activePeriod?->id,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): Response
    {
        Period::syncAutomaticStatus();

        return Inertia::render('Groups/Create', [
            'periods' => Period::orderByDesc('start_date')->get(['id', 'name', 'slug', 'is_active']),
        ]);
    }

    /**
     * Store a new group.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'educational_level' => 'nullable|string|max:255',
            'period_id' => 'required|exists:periods,id',
        ]);

        $validated['slug'] = Group::generateSlug($validated['name']);

        $group = Group::create($validated);

        return redirect()->route('groups.show', $group->slug);
    }

    /**
     * Show a single group (read mode).
     */
    public function show(Group $group): Response
    {
        Period::syncAutomaticStatus();

        $group->load([
            'period',
            'gradeCategories',
            'students' => function ($query) {
                $query->orderBy('first_name')->orderBy('last_name');
            },
        ]);

        $periodId = $group->period_id;

        // Batch: 3 queries total instead of 3-7+ per student (N+1 fix)
        $studentIds = $group->students->pluck('id')->all();
        $summaries = $this->academic->getGroupStudentSummaries($studentIds, $group->id, $periodId);

        $studentsWithSummary = $group->students->map(function (Student $student) use ($summaries) {
            $summary = $summaries[$student->id] ?? [
                'average' => null,
                'attendance' => ['total' => 0, 'present' => 0, 'absent' => 0, 'justified' => 0, 'rate' => null],
                'absence_streak' => 0,
                'has_absence_alert' => false,
                'at_risk' => false,
            ];

            return array_merge($student->toArray(), ['summary' => $summary]);
        });

        $categoryBreakdown = $group->gradeCategories->map(fn (GradeCategory $cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'weight' => $cat->weight,
        ]);

        $totalWeight = $group->gradeCategories->sum('weight');

        // All available students in the institution who are not yet enrolled in this group
        $availableStudents = Student::whereNotIn('id', $studentIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'slug']);

        $allPeriods = Period::orderByDesc('start_date')->get(['id', 'name', 'is_active']);

        return Inertia::render('Groups/Show', [
            'group' => $group,
            'students' => $studentsWithSummary,
            'categories' => $categoryBreakdown,
            'totalWeight' => $totalWeight,
            'availableStudents' => $availableStudents,
            'allPeriods' => $allPeriods,
        ]);
    }

    /**
     * Toggle archived status.
     */
    public function toggleArchive(Group $group): RedirectResponse
    {
        $group->update(['is_archived' => ! $group->is_archived]);

        return back();
    }

    /**
     * Move group to another academic period.
     */
    public function movePeriod(Request $request, Group $group): RedirectResponse
    {
        $validated = $request->validate([
            'period_id' => 'required|exists:periods,id',
        ]);

        $oldPeriodId = $group->period_id;
        $newPeriodId = (int) $validated['period_id'];

        if ($oldPeriodId !== $newPeriodId) {
            $group->update(['period_id' => $newPeriodId]);

            // Synchronize enrolled student pivot records to new period
            $studentIds = $group->students()->pluck('students.id')->all();
            if (! empty($studentIds)) {
                $group->students()->wherePivot('period_id', $oldPeriodId)->updateExistingPivot(
                    $studentIds,
                    ['period_id' => $newPeriodId]
                );
            }
        }

        return back()->with('status', 'Grupo reubicado al periodo seleccionado exitosamente.');
    }

    /**
     * Add a single student to this group.
     */
    public function addStudent(Request $request, Group $group): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        // Check if already in this group for this period
        $exists = $group->students()
            ->wherePivot('student_id', $validated['student_id'])
            ->wherePivot('period_id', $group->period_id)
            ->exists();

        if (! $exists) {
            $group->students()->attach($validated['student_id'], [
                'period_id' => $group->period_id,
            ]);
        }

        return back();
    }

    /**
     * Bulk enroll multiple students into this group.
     */
    public function bulkAddStudents(Request $request, Group $group): RedirectResponse
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $currentStudentIds = $group->students()
            ->wherePivot('period_id', $group->period_id)
            ->pluck('students.id')
            ->all();

        $toAttach = array_diff($validated['student_ids'], $currentStudentIds);

        foreach ($toAttach as $studentId) {
            $group->students()->attach($studentId, [
                'period_id' => $group->period_id,
            ]);
        }

        return back()->with('status', count($toAttach).' estudiantes inscritos exitosamente.');
    }

    /**
     * Remove / unenroll a student from this group.
     */
    public function removeStudent(Group $group, int $studentId): RedirectResponse
    {
        $group->students()->wherePivot('period_id', $group->period_id)->detach($studentId);

        return back();
    }
}
