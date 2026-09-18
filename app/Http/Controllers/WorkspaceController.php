<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Observation;
use App\Models\Period;
use App\Models\Student;
use App\Services\Automation\AutomationContext;
use App\Services\Automation\AutomationEngine;
use App\Services\Insights\InsightContext;
use App\Services\Insights\InsightEngine;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function __construct(
        protected InsightEngine $insightEngine,
        protected AutomationEngine $automationEngine,
        protected NotificationService $notificationService,
    ) {}

    public function index(): Response
    {
        $activePeriod = Period::where('is_active', true)->first();

        $groupsQuery = Group::where('is_archived', false);
        if ($activePeriod !== null) {
            $groupsQuery->where('period_id', $activePeriod->id);
        }
        $groups = $groupsQuery
            ->withCount('students')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Group $g): array => [
                'id' => $g->id,
                'name' => $g->name,
                'slug' => $g->slug,
                'subject' => $g->subject,
                'grade_level' => $g->educational_level,
                'students_count' => $g->students_count,
            ]);

        $pendingObservations = Observation::where('status', 'pending')
            ->with(['student', 'group'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Observation $obs) => [
                'id' => $obs->id,
                'type' => $obs->type,
                'content' => Str::limit($obs->content, 90),
                'student' => $obs->student ? [
                    'id' => $obs->student->id,
                    'first_name' => $obs->student->first_name,
                    'last_name' => $obs->student->last_name,
                    'full_name' => $obs->student->full_name,
                    'initials' => $obs->student->initials,
                    'slug' => $obs->student->slug,
                ] : null,
                'student_name' => $obs->student?->full_name,
                'student_slug' => $obs->student?->slug,
                'group_name' => $obs->group?->name,
                'group_slug' => $obs->group?->slug,
                'created_at' => $obs->created_at?->diffForHumans(),
            ]);

        $recentActivity = Observation::with(['student', 'group'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Observation $obs) => [
                'id' => $obs->id,
                'type' => $obs->type,
                'status' => $obs->status,
                'content' => Str::limit($obs->content, 75),
                'student' => $obs->student ? [
                    'id' => $obs->student->id,
                    'first_name' => $obs->student->first_name,
                    'last_name' => $obs->student->last_name,
                    'full_name' => $obs->student->full_name,
                    'initials' => $obs->student->initials,
                    'slug' => $obs->student->slug,
                ] : null,
                'student_name' => $obs->student?->full_name,
                'group_name' => $obs->group?->name,
                'created_at' => $obs->created_at?->diffForHumans(),
            ]);

        $totalStudents = Student::count();
        $totalGroups = Group::where('is_archived', false)->count();
        // Reuse pendingObservations query result to avoid duplicate COUNT query
        $pendingCount = Observation::where('status', 'pending')->count();

        // Calculate period progress percentage
        $periodProgress = 0;
        if ($activePeriod !== null && $activePeriod->start_date && $activePeriod->end_date) {
            $start = $activePeriod->start_date->startOfDay();
            $end = $activePeriod->end_date->startOfDay();
            $today = now()->startOfDay();
            $totalDays = max(1, (int) $start->diffInDays($end));
            $elapsedDays = min($totalDays, max(0, (int) $start->diffInDays($today)));
            $periodProgress = min(100, (int) round(($elapsedDays / $totalDays) * 100));
        }

        // ── Insights Engine ──
        $insights = [];
        $suggestedActions = [];
        $insightResults = [];

        if ($activePeriod !== null) {
            $insightContext = new InsightContext(periodId: $activePeriod->id);
            $insightResults = $this->insightEngine->generate($insightContext, 10);
            $insights = array_map(
                fn (\App\Services\Insights\InsightResult $i): array => $i->toArray(),
                array_slice($insightResults, 0, 5)
            );

            // ── Automation Engine ──
            $autoContext = AutomationContext::fromActivePeriod($activePeriod, $pendingCount);
            if ($autoContext !== null) {
                $suggestedActions = $this->automationEngine->forWorkspace($autoContext, $insightResults, 5);

                // ── Notification Processing ──
                $userId = Auth::id();
                if ($userId !== null) {
                    $automationActions = $this->automationEngine->generate($autoContext, $insightResults, 10);
                    $this->notificationService->processActions($automationActions, $userId);
                }
            }
        }

        return Inertia::render('Workspace/Index', [
            'activePeriod' => $activePeriod !== null ? [
                'id' => $activePeriod->id,
                'name' => $activePeriod->name,
                'slug' => $activePeriod->slug,
                'start_date' => $activePeriod->start_date instanceof \Carbon\Carbon
                    ? $activePeriod->start_date->format('Y-m-d')
                    : $activePeriod->start_date,
                'end_date' => $activePeriod->end_date instanceof \Carbon\Carbon
                    ? $activePeriod->end_date->format('Y-m-d')
                    : $activePeriod->end_date,
                'days_remaining' => $activePeriod->days_remaining,
                'progress_percent' => $periodProgress,
            ] : null,
            'groups' => $groups,
            'pendingObservations' => $pendingObservations,
            'recentActivity' => $recentActivity,
            'insights' => $insights,
            'suggestedActions' => $suggestedActions,
            'stats' => [
                'total_students' => $totalStudents,
                'total_groups' => $totalGroups,
                'pending_observations' => $pendingCount,
            ],
        ]);
    }
}
