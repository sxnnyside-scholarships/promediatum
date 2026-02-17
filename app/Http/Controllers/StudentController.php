<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Group;
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
     * List all students (scan mode).
     */
    public function index(): Response
    {
        $students = Student::with(['groups.period'])->get();

        return Inertia::render('Students/Index', [
            'students' => $students,
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): Response
    {
        return Inertia::render('Students/Create');
    }

    /**
     * Store a new student.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);

        $validated['slug'] = Student::generateSlug($validated['first_name'], $validated['last_name']);

        $student = Student::create($validated);

        return redirect()->route('students.show', $student->slug);
    }

    /**
     * Show a specific student (read mode).
     */
    public function show(Student $student): Response
    {
        $student->load(['groups.period', 'observations']);

        // Build per-group summaries
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

        return Inertia::render('Students/Show', [
            'student' => $student,
            'groupSummaries' => $groupSummaries,
            'observations' => $student->observations()
                ->with('group')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }
}
