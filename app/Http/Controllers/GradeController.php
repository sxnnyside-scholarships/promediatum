<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    /**
     * Store a new grade.
     */
    public function store(Request $request, Group $group): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'category_id' => 'required|exists:grade_categories,id',
            'title' => 'required|string|max:255',
            'score' => 'required|numeric|min:0|max:999',
            'max_score' => 'required|numeric|min:0.01|max:999',
            'date' => 'required|date',
        ]);

        // Ensure score <= max_score
        if ($validated['score'] > $validated['max_score']) {
            return back()->withErrors(['score' => 'Score cannot exceed max score.']);
        }

        Grade::create([
            'student_id' => $validated['student_id'],
            'group_id' => $group->id,
            'period_id' => $group->period_id,
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'score' => $validated['score'],
            'max_score' => $validated['max_score'],
            'date' => $validated['date'],
        ]);

        return back();
    }

    /**
     * Update a grade.
     */
    public function update(Request $request, Grade $grade): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'score' => 'required|numeric|min:0|max:999',
            'max_score' => 'required|numeric|min:0.01|max:999',
            'date' => 'required|date',
        ]);

        if ($validated['score'] > $validated['max_score']) {
            return back()->withErrors(['score' => 'Score cannot exceed max score.']);
        }

        $grade->update($validated);

        return back();
    }

    /**
     * Delete a grade.
     */
    public function destroy(Grade $grade): RedirectResponse
    {
        $grade->delete();

        return back();
    }
}
