<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Group;
use App\Models\Period;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Display high-level attendance dashboard across all groups.
     */
    public function dashboard(Request $request): Response
    {
        Period::syncAutomaticStatus();
        $activePeriod = Period::active();
        $today = now()->toDateString();

        $groupsQuery = Group::with('period')->withCount('students');
        if ($activePeriod) {
            $groupsQuery->where('period_id', $activePeriod->id);
        }
        $groups = $groupsQuery->where('is_archived', false)
            ->orderBy('name')
            ->get();

        $groupIds = $groups->pluck('id')->all();

        // Today's attendance records grouped by group_id
        $todayRecords = Attendance::whereIn('group_id', $groupIds)
            ->whereDate('date', $today)
            ->get()
            ->groupBy('group_id');

        // Aggregated group attendance stats
        $allGroupAttendances = Attendance::whereIn('group_id', $groupIds)
            ->selectRaw('group_id, count(*) as total, sum(case when status = "present" then 1 else 0 end) as present, max(date) as last_date')
            ->groupBy('group_id')
            ->get()
            ->keyBy('group_id');

        $groupsSummary = $groups->map(function (Group $g) use ($todayRecords, $allGroupAttendances) {
            $todayGroupRecords = $todayRecords->get($g->id, collect());
            $hasToday = $todayGroupRecords->isNotEmpty();
            $todayPresent = $todayGroupRecords->where('status', 'present')->count();
            $todayAbsent = $todayGroupRecords->where('status', 'absent')->count();
            $todayJustified = $todayGroupRecords->where('status', 'justified')->count();
            $todayTotal = $todayGroupRecords->count();

            $allGroupStat = $allGroupAttendances->get($g->id);
            $totalRecorded = $allGroupStat ? (int) $allGroupStat->getAttribute('total') : 0;
            $totalPresent = $allGroupStat ? (int) $allGroupStat->getAttribute('present') : 0;
            $overallRate = $totalRecorded > 0 ? round(($totalPresent / $totalRecorded) * 100, 1) : null;
            $lastDate = $allGroupStat?->getAttribute('last_date');

            return [
                'id' => $g->id,
                'name' => $g->name,
                'subject' => $g->subject,
                'educational_level' => $g->educational_level,
                'slug' => $g->slug,
                'students_count' => $g->students_count,
                'period_name' => $g->period?->name,
                'has_today' => $hasToday,
                'today_stats' => [
                    'total' => $todayTotal,
                    'present' => $todayPresent,
                    'absent' => $todayAbsent,
                    'justified' => $todayJustified,
                    'rate' => $todayTotal > 0 ? round(($todayPresent / $todayTotal) * 100, 1) : null,
                ],
                'overall_rate' => $overallRate,
                'last_date' => $lastDate ? Carbon::parse((string) $lastDate)->toDateString() : null,
            ];
        });

        $totalStudents = $groups->sum('students_count');
        $groupsCompletedToday = $groupsSummary->filter(fn ($g) => $g['has_today'])->count();

        return Inertia::render('Attendance/Dashboard', [
            'activePeriod' => $activePeriod ? [
                'id' => $activePeriod->id,
                'name' => $activePeriod->name,
                'slug' => $activePeriod->slug,
            ] : null,
            'groups' => $groupsSummary,
            'kpis' => [
                'total_groups' => $groups->count(),
                'groups_completed_today' => $groupsCompletedToday,
                'total_students' => $totalStudents,
                'today' => $today,
            ],
        ]);
    }

    /**
     * Show attendance view for a group on a given date (Daily roll-call & group matrix).
     */
    public function index(Request $request, Group $group): Response
    {
        Period::syncAutomaticStatus();
        $date = $request->input('date', now()->toDateString());

        $group->load([
            'period',
            'students' => fn ($q) => $q->orderBy('first_name')->orderBy('last_name'),
        ]);

        // Existing records for the selected date
        $existing = Attendance::where('group_id', $group->id)
            ->whereDate('date', $date)
            ->pluck('status', 'student_id')
            ->toArray();

        // Historical attendance for group
        $allAttendance = Attendance::where('group_id', $group->id)
            ->orderBy('date', 'desc')
            ->get();

        // Extract unique session dates (up to 20 recent sessions)
        $sessionDates = $allAttendance->map(function ($rec) {
            return $rec->date instanceof Carbon ? $rec->date->toDateString() : substr((string) $rec->date, 0, 10);
        })->unique()->values()->take(20)->all();

        // Ensure currently viewed date is included in session dates
        if (! in_array($date, $sessionDates, true)) {
            array_unshift($sessionDates, $date);
            $sessionDates = array_slice($sessionDates, 0, 20);
        }

        $byStudent = $allAttendance->groupBy('student_id');

        $students = $group->students->map(function (Student $s) use ($existing, $byStudent, $sessionDates) {
            $history = $byStudent->get($s->id, collect());
            $total = $history->count();
            $present = $history->where('status', 'present')->count();
            $absent = $history->where('status', 'absent')->count();
            $justified = $history->where('status', 'justified')->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : null;

            // Calculate consecutive absence streak (newest first)
            $streak = 0;
            $sorted = $history->sortByDesc('date');
            foreach ($sorted as $rec) {
                if ($rec->status === 'absent') {
                    $streak++;
                } else {
                    break;
                }
            }

            // Map status for each session in sessionDates
            $dateMap = [];
            foreach ($history as $rec) {
                $recDate = $rec->date instanceof Carbon ? $rec->date->toDateString() : substr((string) $rec->date, 0, 10);
                $dateMap[$recDate] = $rec->status;
            }

            $matrixRow = [];
            foreach ($sessionDates as $sd) {
                $matrixRow[$sd] = $dateMap[$sd] ?? null;
            }

            return [
                'id' => $s->id,
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'full_name' => $s->full_name,
                'initials' => $s->initials,
                'slug' => $s->slug,
                'status' => $existing[$s->id] ?? null,
                'summary' => [
                    'total' => $total,
                    'present' => $present,
                    'absent' => $absent,
                    'justified' => $justified,
                    'rate' => $rate,
                    'absence_streak' => $streak,
                    'has_absence_alert' => $streak >= 3,
                ],
                'matrix' => $matrixRow,
            ];
        });

        // Other groups in same period for quick switcher
        $otherGroups = Group::where('period_id', $group->period_id)
            ->where('is_archived', false)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $totalStudents = $students->count();
        $presentCount = $students->where('status', 'present')->count();
        $absentCount = $students->where('status', 'absent')->count();
        $justifiedCount = $students->where('status', 'justified')->count();
        $pendingCount = $totalStudents - ($presentCount + $absentCount + $justifiedCount);

        return Inertia::render('Attendance/Index', [
            'group' => $group,
            'date' => $date,
            'students' => $students,
            'sessionDates' => $sessionDates,
            'otherGroups' => $otherGroups,
            'stats' => [
                'total' => $totalStudents,
                'present' => $presentCount,
                'absent' => $absentCount,
                'justified' => $justifiedCount,
                'pending' => $pendingCount,
                'rate' => $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 1) : 0,
            ],
        ]);
    }

    /**
     * Bulk save attendance for a group + date.
     */
    public function store(Request $request, Group $group): RedirectResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'records' => 'required|array',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:present,absent,justified',
        ]);

        foreach ($validated['records'] as $record) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $record['student_id'],
                    'group_id' => $group->id,
                    'date' => $validated['date'],
                ],
                [
                    'period_id' => $group->period_id,
                    'status' => $record['status'],
                ]
            );
        }

        return back()->with('status', 'Asistencia guardada exitosamente.');
    }

    /**
     * Update a single attendance record inline.
     */
    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:present,absent,justified',
        ]);

        $attendance->update($validated);

        return back();
    }
}
