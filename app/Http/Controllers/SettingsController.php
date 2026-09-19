<?php

namespace App\Http\Controllers;

use App\Services\Backup\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    /**
     * Show the settings page.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'settings' => auth()->user()->settings ?? [],
            'backups' => $this->backupService->list(),
        ]);
    }

    /**
     * Update interface preferences (persisted in users.settings JSON).
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sidebar_position' => ['sometimes', 'in:leading,trailing'],
            'text_weight' => ['sometimes', 'in:300,400,500,600'],
            'fab_enabled' => ['sometimes', 'boolean'],
            'visual_effects_enabled' => ['sometimes', 'boolean'],
            'icons_enabled' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $current = $user->settings ?? [];
        $user->settings = array_merge($current, $validated);
        $user->save();

        return back();
    }
}
