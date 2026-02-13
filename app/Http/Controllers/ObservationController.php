<?php

namespace App\Http\Controllers;

use App\Models\Observation;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ObservationController extends Controller
{
    /**
     * List all observations (scan mode) with filters.
     */
    public function index(Request $request): Response
    {
        $query = Observation::with(['student', 'group', 'period'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->input('group_id'));
        }

        $observations = $query->get();

        return Inertia::render('Observations/Index', [
            'observations' => $observations,
            'groups' => Group::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['status', 'type', 'group_id']),
        ]);
    }

    /**
     * Store a new observation.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'group_id' => 'required|exists:groups,id',
            'type' => 'required|in:performance,behavior,achievement,followup',
            'content' => 'required|string|max:2000',
        ]);

        $group = Group::findOrFail($validated['group_id']);

        Observation::create([
            'student_id' => $validated['student_id'],
            'group_id' => $validated['group_id'],
            'period_id' => $group->period_id,
            'type' => $validated['type'],
            'content' => $validated['content'],
            'status' => 'pending',
        ]);

        return back();
    }

    /**
     * Toggle observation resolved/pending.
     */
    public function toggleResolved(Observation $observation): RedirectResponse
    {
        if ($observation->status === 'resolved') {
            $observation->update([
                'status' => 'pending',
                'resolved_at' => null,
            ]);
        } else {
            $observation->resolve();
        }

        return back();
    }

    /**
     * Delete an observation.
     */
    public function destroy(Observation $observation): RedirectResponse
    {
        $observation->delete();
        return back();
    }
}
