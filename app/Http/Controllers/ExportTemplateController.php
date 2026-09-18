<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportTemplateRequest;
use App\Models\ExportTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExportTemplateController extends Controller
{
    /**
     * GET /exports/templates — List all templates.
     */
    public function index(): Response
    {
        $templates = ExportTemplate::where('user_id', Auth::id())
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (ExportTemplate $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'type' => $t->type,
                'is_default' => $t->is_default,
                'config' => $t->normalized_config,
                'created_at' => $t->created_at->toDateTimeString(),
                'updated_at' => $t->updated_at->toDateTimeString(),
            ]);

        return Inertia::render('Exports/Templates/Index', [
            'templates' => $templates,
        ]);
    }

    /**
     * GET /exports/templates/create — Show template creation form.
     */
    public function create(): Response
    {
        return Inertia::render('Exports/Templates/Create', [
            'defaultConfig' => ExportTemplate::defaultConfig(),
        ]);
    }

    /**
     * POST /exports/templates — Store a new template.
     */
    public function store(ExportTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // If setting as default, unset other defaults for same type
        if (! empty($data['is_default'])) {
            ExportTemplate::where('user_id', Auth::id())
                ->where('type', $data['type'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        ExportTemplate::create([
            'user_id' => Auth::id(),
            'name' => $data['name'],
            'type' => $data['type'],
            'is_default' => $data['is_default'] ?? false,
            'config' => $data['config'],
        ]);

        return redirect()->route('exports.templates.index')
            ->with('success', 'Template created successfully.');
    }

    /**
     * GET /exports/templates/{exportTemplate}/edit — Show edit form.
     */
    public function edit(ExportTemplate $exportTemplate): Response
    {
        // Security: only the owner can edit
        if ($exportTemplate->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('Exports/Templates/Edit', [
            'template' => [
                'id' => $exportTemplate->id,
                'name' => $exportTemplate->name,
                'type' => $exportTemplate->type,
                'is_default' => $exportTemplate->is_default,
                'config' => $exportTemplate->normalized_config,
            ],
            'defaultConfig' => ExportTemplate::defaultConfig(),
        ]);
    }

    /**
     * PUT /exports/templates/{exportTemplate} — Update a template.
     */
    public function update(ExportTemplateRequest $request, ExportTemplate $exportTemplate): RedirectResponse
    {
        if ($exportTemplate->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validated();

        // If setting as default, unset other defaults for same type
        if (! empty($data['is_default'])) {
            ExportTemplate::where('user_id', Auth::id())
                ->where('type', $data['type'])
                ->where('is_default', true)
                ->where('id', '!=', $exportTemplate->id)
                ->update(['is_default' => false]);
        }

        $exportTemplate->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'is_default' => $data['is_default'] ?? false,
            'config' => $data['config'],
        ]);

        return redirect()->route('exports.templates.index')
            ->with('success', 'Template updated successfully.');
    }

    /**
     * DELETE /exports/templates/{exportTemplate} — Delete a template.
     */
    public function destroy(ExportTemplate $exportTemplate): RedirectResponse
    {
        if ($exportTemplate->user_id !== Auth::id()) {
            abort(403);
        }

        $exportTemplate->delete();

        return redirect()->route('exports.templates.index')
            ->with('success', 'Template deleted successfully.');
    }
}
