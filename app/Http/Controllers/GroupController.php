<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Period;
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
     * List all groups (scan mode).
     */
    public function index(): Response
    {
        $groups = Group::with('period')
            ->withCount('students')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
            'periods' => Period::orderByDesc('start_date')->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): Response
    {
        return Inertia::render('Groups/Create', [
            'periods' => Period::orderByDesc('start_date')->get(['id', 'name', 'slug']),
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
        $group->load(['period', 'students', 'gradeCategories']);

        $periodId = $group->period_id;
        $studentsWithSummary = $group->students->map(function ($student) use ($group, $periodId) {
            $summary = $this->academic->getStudentSummary($student->id, $group->id, $periodId);
            return array_merge($student->toArray(), ['summary' => $summary]);
        });

        $categoryBreakdown = $group->gradeCategories->map(fn ($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'weight' => $cat->weight,
        ]);

        $totalWeight = $group->gradeCategories->sum('weight');

        return Inertia::render('Groups/Show', [
            'group' => $group,
            'students' => $studentsWithSummary,
            'categories' => $categoryBreakdown,
            'totalWeight' => $totalWeight,
        ]);
    }

    /**
     * Toggle archived status.
     */
    public function toggleArchive(Group $group): RedirectResponse
    {
        $group->update(['is_archived' => !$group->is_archived]);
        return back();
    }

    /**
     * Add a student to this group.
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

        if (!$exists) {
            $group->students()->attach($validated['student_id'], [
                'period_id' => $group->period_id,
            ]);
        }

        return back();
    }

    /**
     * Remove a student from this group.
     */
    public function removeStudent(Group $group, int $studentId): RedirectResponse
    {
        $group->students()->wherePivot('period_id', $group->period_id)->detach($studentId);
        return back();
    }
}
