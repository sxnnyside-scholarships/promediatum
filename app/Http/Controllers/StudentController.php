<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Group;
use App\Models\Student;
use App\Services\AcademicService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(
        protected AcademicService $academic
    ) {}

    /**
     * List all students with ergonomics, search, and metrics.
     */
    public function index(): Response
    {
        $students = Student::with(['groups.period'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $groups = Group::with('period')
            ->orderBy('name')
            ->get();

        // Calculate summary metrics for each student for smart filtering and badges
        $metrics = $students->mapWithKeys(function (Student $student) {
            $totalAvg = 0;
            $avgCount = 0;
            $hasAlert = false;
            $atRisk = false;

            foreach ($student->groups as $group) {
                $periodId = $group->pivot->period_id ?? $group->period_id;
                $summary = $this->academic->getStudentSummary($student->id, $group->id, $periodId);

                if ($summary['average'] !== null) {
                    $totalAvg += $summary['average'];
                    $avgCount++;
                }
                if ($summary['at_risk']) {
                    $atRisk = true;
                }
                if ($summary['has_absence_alert']) {
                    $hasAlert = true;
                }
            }

            return [$student->id => [
                'average' => $avgCount > 0 ? round($totalAvg / $avgCount, 1) : null,
                'at_risk' => $atRisk,
                'has_absence_alert' => $hasAlert,
                'groups_count' => $student->groups->count(),
            ]];
        });

        return Inertia::render('Students/Index', [
            'students' => $students,
            'groups' => $groups,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Show create form with available groups for multi-group assignment.
     */
    public function create(): Response
    {
        $availableGroups = Group::with('period')
            ->orderBy('name')
            ->get();

        return Inertia::render('Students/Create', [
            'availableGroups' => $availableGroups,
        ]);
    }

    /**
     * Store a new student with contact data, notes, and multi-group enrollment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'guardian_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'integer|exists:groups,id',
        ]);

        $validated['slug'] = Student::generateSlug($validated['first_name'], $validated['last_name']);

        $student = Student::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'slug' => $validated['slug'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if (! empty($validated['group_ids'])) {
            $groups = Group::whereIn('id', $validated['group_ids'])->get();
            $attachData = [];
            foreach ($groups as $group) {
                $attachData[$group->id] = ['period_id' => $group->period_id];
            }
            $student->groups()->sync($attachData);
        }

        return redirect()->route('students.show', $student->slug);
    }

    /**
     * Show a specific student profile with performance charts and records.
     */
    public function show(Student $student): Response
    {
        $student->load(['groups.period', 'observations.group']);

        // Build per-group summaries and category breakdown
        $groupSummaries = $student->groups->map(function (Group $group) use ($student) {
            $periodId = $group->pivot->period_id ?? $group->period_id;
            $summary = $this->academic->getStudentSummary(
                $student->id,
                $group->id,
                $periodId
            );
            $categories = $this->academic->getCategoryBreakdown(
                $student->id,
                $group->id,
                $periodId
            );

            return [
                'group' => $group,
                'summary' => $summary,
                'categories' => $categories,
            ];
        });

        // Load chronological grades for performance charts
        $gradesHistory = Grade::where('student_id', $student->id)
            ->with(['category', 'group'])
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (Grade $g) {
                return [
                    'id' => $g->id,
                    'title' => $g->title,
                    'score' => (float) $g->score,
                    'max_score' => (float) $g->max_score,
                    'percentage' => $g->percentage,
                    'date' => $g->date ? $g->date->format('Y-m-d') : null,
                    'formatted_date' => $g->date ? $g->date->format('d M') : null,
                    'category_name' => $g->category?->name,
                    'category_id' => $g->category_id,
                    'group_name' => $g->group?->name,
                    'group_id' => $g->group_id,
                ];
            });

        $allGroups = Group::with('period')->orderBy('name')->get();

        return Inertia::render('Students/Show', [
            'student' => $student,
            'groupSummaries' => $groupSummaries,
            'gradesHistory' => $gradesHistory,
            'allGroups' => $allGroups,
            'observations' => $student->observations()
                ->with('group')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    /**
     * Update student profile, contact info, notes, and group enrollments.
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'guardian_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'integer|exists:groups,id',
        ]);

        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        if ($student->first_name !== $validated['first_name'] || $student->last_name !== $validated['last_name']) {
            $updateData['slug'] = Student::generateSlug($validated['first_name'], $validated['last_name']);
        }

        $student->update($updateData);

        if (isset($validated['group_ids'])) {
            $groups = Group::whereIn('id', $validated['group_ids'])->get();
            $attachData = [];
            foreach ($groups as $group) {
                $attachData[$group->id] = ['period_id' => $group->period_id];
            }
            $student->groups()->sync($attachData);
        }

        return redirect()->route('students.show', $student->fresh()->slug);
    }
}
