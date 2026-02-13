<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Show attendance view for a group on a given date.
     */
    public function index(Request $request, Group $group): Response
    {
        $date = $request->input('date', now()->toDateString());

        $group->load(['students', 'period']);

        // Existing records for this date
        $existing = Attendance::where('group_id', $group->id)
            ->where('date', $date)
            ->pluck('status', 'student_id')
            ->toArray();

        $students = $group->students->map(fn ($s) => [
            'id' => $s->id,
            'full_name' => $s->full_name,
            'status' => $existing[$s->id] ?? null,
        ]);

        return Inertia::render('Attendance/Index', [
            'group' => $group,
            'date' => $date,
            'students' => $students,
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

        return back();
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
