<?php

namespace App\Http\Controllers;

use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeriodController extends Controller
{
    /**
     * Display a listing of periods (scan mode).
     */
    public function index(): Response
    {
        $periods = Period::orderByDesc('start_date')->get();

        return Inertia::render('Periods/Index', [
            'periods' => $periods,
        ]);
    }

    /**
     * Show the form for creating a new period (read mode).
     */
    public function create(): Response
    {
        return Inertia::render('Periods/Create');
    }

    /**
     * Store a newly created period.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $validated['slug'] = Period::generateSlug($validated['name']);
        $validated['start_date'] = Carbon::createFromFormat('Y-m-d', $validated['start_date']);
        $validated['end_date'] = Carbon::createFromFormat('Y-m-d', $validated['end_date']);

        $period = Period::create($validated);

        // If this is the first period, activate it automatically
        if (Period::count() === 1) {
            $period->activate();
        }

        return redirect()->route('periods.show', $period->slug)
            ->with('status', 'Periodo creado exitosamente.');
    }

    /**
     * Display a specific period (read mode).
     */
    public function show(Period $period): Response
    {
        return Inertia::render('Periods/Show', [
            'period' => $period,
        ]);
    }

    /**
     * Toggle active state for a period.
     */
    public function toggleActive(Period $period): RedirectResponse
    {
        if ($period->is_active) {
            $period->deactivate();
        } else {
            $period->activate();
        }

        return back();
    }
}
