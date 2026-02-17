<?php

namespace App\Services\UI;

use App\Models\Group;
use App\Models\Observation;
use App\Models\Period;
use App\Services\Automation\AutomationAction;
use App\Services\Automation\AutomationContext;
use App\Services\Automation\AutomationEngine;
use App\Services\Insights\InsightContext;
use App\Services\Insights\InsightEngine;
use Illuminate\Support\Facades\Auth;

/**
 * FabActionResolver — Determines contextual FAB actions.
 *
 * Analyses current route + application state to produce a prioritized list
 * of up to 3 actions for the Intelligent FAB.
 *
 * Priority hierarchy:
 *   1. Route-specific primary action (highest relevance)
 *   2. State-aware alerts (pending observations, missing attendance)
 *   3. Common creation shortcuts
 *
 * The resolver returns serializable arrays so the controller can pass
 * them to Inertia as shared props.
 */
class FabActionResolver
{
    /**
     * Resolve actions for a given route context.
     *
     * @param  string      $currentRoute  Named route (e.g. 'workspace', 'groups.show')
     * @param  array       $params        Route parameters (group slug, etc.)
     * @param  array       $appState      Pre-fetched state: pending_observations, active_period, etc.
     * @return array<int, array{label: string, icon: string, route: string, params?: array, priority: int}>
     */
    public function resolve(string $currentRoute, array $params = [], array $appState = []): array
    {
        $actions = [];

        // Automation-driven actions (highest priority)
        $actions = array_merge($actions, $this->automationActions($currentRoute, $appState));

        // Route-specific primary actions
        $actions = array_merge($actions, $this->routeActions($currentRoute, $params));

        // State-aware actions
        $actions = array_merge($actions, $this->stateActions($currentRoute, $appState));

        // Sort by priority (lower number = higher priority)
        usort($actions, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        // Max 3 actions
        return array_slice($actions, 0, 3);
    }

    /**
     * Gather minimal application state for the resolver.
     * Called by the controller/middleware to pre-fetch data once per request.
     */
    public function gatherState(): array
    {
        $userId = Auth::id();
        if (! $userId) {
            return [];
        }

        $activePeriod = Period::where('is_active', true)->first();

        return [
            'active_period_id'      => $activePeriod?->id,
            'active_period_name'    => $activePeriod?->name,
            'pending_observations'  => Observation::where('status', 'pending')->count(),
            'group_count'           => Group::count(),
        ];
    }

    // ────────────────────────────────────────────────
    // ROUTE-SPECIFIC ACTIONS
    // ────────────────────────────────────────────────

    protected function routeActions(string $currentRoute, array $params): array
    {
        return match (true) {
            // Workspace / Dashboard
            str_starts_with($currentRoute, 'workspace') => [
                ['label' => 'fab.new_group', 'icon' => 'users', 'route' => 'groups.create', 'priority' => 10],
                ['label' => 'fab.new_student', 'icon' => 'user', 'route' => 'students.create', 'priority' => 20],
                ['label' => 'fab.new_period', 'icon' => 'calendar', 'route' => 'periods.create', 'priority' => 30],
            ],

            // Groups index
            $currentRoute === 'groups.index' => [
                ['label' => 'fab.new_group', 'icon' => 'users', 'route' => 'groups.create', 'priority' => 10],
            ],

            // Group show — attendance is the primary action
            $currentRoute === 'groups.show' => [
                ['label' => 'fab.take_attendance', 'icon' => 'clipboard', 'route' => 'attendance.index', 'params' => $params, 'priority' => 5],
                ['label' => 'fab.export_group', 'icon' => 'download', 'route' => 'exports.history', 'priority' => 15],
            ],

            // Students show — export is contextual
            $currentRoute === 'students.show' => [
                ['label' => 'fab.new_observation', 'icon' => 'book', 'route' => 'observations.index', 'priority' => 10],
                ['label' => 'fab.export_student', 'icon' => 'download', 'route' => 'exports.history', 'priority' => 15],
            ],

            // Students index
            $currentRoute === 'students.index' => [
                ['label' => 'fab.new_student', 'icon' => 'user', 'route' => 'students.create', 'priority' => 10],
            ],

            // Periods
            str_starts_with($currentRoute, 'periods') => [
                ['label' => 'fab.new_period', 'icon' => 'calendar', 'route' => 'periods.create', 'priority' => 10],
                ['label' => 'fab.export_period', 'icon' => 'download', 'route' => 'exports.history', 'priority' => 15],
            ],

            // Observations
            str_starts_with($currentRoute, 'observations') => [
                ['label' => 'fab.new_observation', 'icon' => 'book', 'route' => 'observations.index', 'priority' => 10],
            ],

            // Exports
            str_starts_with($currentRoute, 'exports') => [
                ['label' => 'fab.new_export', 'icon' => 'download', 'route' => 'exports.history', 'priority' => 10],
            ],

            // Default
            default => [
                ['label' => 'fab.new_group', 'icon' => 'users', 'route' => 'groups.create', 'priority' => 20],
                ['label' => 'fab.new_student', 'icon' => 'user', 'route' => 'students.create', 'priority' => 25],
            ],
        };
    }

    // ────────────────────────────────────────────────
    // STATE-AWARE ACTIONS
    // ────────────────────────────────────────────────

    protected function stateActions(string $currentRoute, array $appState): array
    {
        $actions = [];

        // Show pending observations alert on dashboard
        $pending = $appState['pending_observations'] ?? 0;
        if ($pending > 0 && str_starts_with($currentRoute, 'workspace')) {
            $actions[] = [
                'label'    => 'fab.pending_observations',
                'icon'     => 'alert-triangle',
                'route'    => 'observations.index',
                'priority' => 1, // Highest priority — alerts first
                'badge'    => $pending,
            ];
        }

        return $actions;
    }

    // ────────────────────────────────────────────────
    // AUTOMATION-DRIVEN ACTIONS
    // ────────────────────────────────────────────────

    protected function automationActions(string $currentRoute, array $appState): array
    {
        // Only inject automation actions on workspace
        if (! str_starts_with($currentRoute, 'workspace')) {
            return [];
        }

        $periodId = $appState['active_period_id'] ?? null;
        if ($periodId === null) {
            return [];
        }

        try {
            $period = Period::find($periodId);
            if ($period === null) {
                return [];
            }

            $pendingObs = $appState['pending_observations'] ?? 0;
            $autoContext = AutomationContext::fromActivePeriod($period, $pendingObs);
            if ($autoContext === null) {
                return [];
            }

            $insightContext = new InsightContext(periodId: $periodId);
            /** @var InsightEngine $insightEngine */
            $insightEngine = app(InsightEngine::class);
            $insightResults = $insightEngine->generate($insightContext, 10);

            /** @var AutomationEngine $automationEngine */
            $automationEngine = app(AutomationEngine::class);
            $urgentActions = $automationEngine->forFab($autoContext, $insightResults, 3);

            $fabActions = [];
            foreach ($urgentActions as $action) {
                $fabActions[] = [
                    'label'    => $action->title,
                    'icon'     => $action->icon,
                    'route'    => $action->route ?? 'workspace',
                    'priority' => $action->severityWeight() <= 3 ? 0 : -1, // Higher severity = lower priority number
                    'badge'    => null,
                ];
            }

            return $fabActions;
        } catch (\Throwable) {
            return []; // Fail silently — FAB should always work
        }
    }
}
