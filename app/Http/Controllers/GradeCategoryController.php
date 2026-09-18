<?php

namespace App\Http\Controllers;

use App\Models\GradeCategory;
use App\Models\Group;
use App\Services\AcademicService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GradeCategoryController extends Controller
{
    public function __construct(
        protected AcademicService $academic
    ) {}

    /**
     * Store a new grade category for a group.
     */
    public function store(Request $request, Group $group): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0.01|max:100',
        ]);

        // Check total weights won't exceed 100
        $currentTotal = $this->academic->validateCategoryWeights($group->id);
        if (($currentTotal + $validated['weight']) > 100) {
            return back()->withErrors([
                'weight' => 'Total category weights cannot exceed 100%. Current total: '.$currentTotal.'%.',
            ]);
        }

        GradeCategory::create([
            'group_id' => $group->id,
            'name' => $validated['name'],
            'weight' => $validated['weight'],
        ]);

        return back();
    }

    /**
     * Update a grade category.
     */
    public function update(Request $request, GradeCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0.01|max:100',
        ]);

        // Check total weights won't exceed 100 (excluding this category)
        $currentTotal = $this->academic->validateCategoryWeights($category->group_id, $category->id);
        if (($currentTotal + $validated['weight']) > 100) {
            return back()->withErrors([
                'weight' => 'Total category weights cannot exceed 100%. Current total (excluding this): '.$currentTotal.'%.',
            ]);
        }

        $category->update($validated);

        return back();
    }

    /**
     * Delete a grade category (and its grades via cascade).
     */
    public function destroy(GradeCategory $category): RedirectResponse
    {
        $category->grades()->delete();
        $category->delete();

        return back();
    }
}
