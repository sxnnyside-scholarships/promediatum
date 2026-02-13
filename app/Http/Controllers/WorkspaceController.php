<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Observation;
use App\Models\Period;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(): Response
    {
        // Active period
        $activePeriod = Period::where('is_active', true)->first();

        // Groups with student counts (for active period, or all if none active)
        $groupsQuery = Group::where('is_archived', false);
        if ($activePeriod) {
            $groupsQuery->where('period_id', $activePeriod->id);
        }
        $groups = $groupsQuery
            ->withCount('students')
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'slug' => $g->slug,
                'subject' => $g->subject,
                'students_count' => $g->students_count,
            ]);

        // Pending observations
        $pendingObservations = Observation::where('status', 'pending')
            ->with(['student:id,first_name,last_name,slug', 'group:id,name,slug'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($obs) => [
                'id' => $obs->id,
                'type' => $obs->type,
                'content' => \Illuminate\Support\Str::limit($obs->content, 80),
                'student_name' => $obs->student?->full_name,
                'student_slug' => $obs->student?->slug,
                'group_name' => $obs->group?->name,
                'group_slug' => $obs->group?->slug,
                'created_at' => $obs->created_at->diffForHumans(),
            ]);

        // Recent activity — latest 8 observations (any status)
        $recentActivity = Observation::with(['student:id,first_name,last_name', 'group:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn ($obs) => [
                'id' => $obs->id,
                'type' => $obs->type,
                'status' => $obs->status,
                'content' => \Illuminate\Support\Str::limit($obs->content, 60),
                'student_name' => $obs->student?->full_name,
                'group_name' => $obs->group?->name,
                'created_at' => $obs->created_at->diffForHumans(),
            ]);

        // Quick stats
        $totalStudents = Student::count();
        $totalGroups = Group::where('is_archived', false)->count();
        $pendingCount = Observation::where('status', 'pending')->count();

        return Inertia::render('Workspace/Index', [
            'activePeriod' => $activePeriod ? [
                'id' => $activePeriod->id,
                'name' => $activePeriod->name,
                'slug' => $activePeriod->slug,
                'start_date' => $activePeriod->start_date->format('Y-m-d'),
                'end_date' => $activePeriod->end_date->format('Y-m-d'),
                'days_remaining' => $activePeriod->days_remaining,
            ] : null,
            'groups' => $groups,
            'pendingObservations' => $pendingObservations,
            'recentActivity' => $recentActivity,
            'stats' => [
                'total_students' => $totalStudents,
                'total_groups' => $totalGroups,
                'pending_observations' => $pendingCount,
            ],
        ]);
    }
}
