<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Services\Automation\Rules\ConsecutiveAbsencesFollowUpRule;
use App\Services\Automation\Rules\HighRiskObservationRule;
use App\Services\Automation\Rules\PeriodEndingExportRule;
use App\Services\Automation\Rules\UnresolvedObservationsReviewRule;
use App\Services\Insights\InsightResult;
use Illuminate\Support\Facades\Cache;

/**
 * AutomationEngine — Evaluates automation rules against insights and context.
 *
 * Consumes InsightResults produced by the InsightEngine, plus contextual
 * state from AutomationContext, to generate prioritized AutomationActions.
 *
 * Key guarantees:
 *  - Never auto-executes destructive actions
 *  - Returns suggestions only
 *  - Deduplicates by rule key
 *  - Sorted by severity (highest first)
 */
final class AutomationEngine
{
    /** Cache TTL for automation results (15 minutes). */
    private const CACHE_TTL = 900;

    /** Cache key prefix. */
    private const CACHE_PREFIX = 'automation:period:';

    /** @var AutomationRule[] */
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            new HighRiskObservationRule,
            new ConsecutiveAbsencesFollowUpRule,
            new PeriodEndingExportRule,
            new UnresolvedObservationsReviewRule,
        ];
    }

    /**
     * Generate automation actions for the given context and insights.
     *
     * @param  InsightResult[]  $insights
     * @return AutomationAction[]
     */
    public function generate(AutomationContext $context, array $insights, int $limit = 5): array
    {
        $cacheKey = $this->cacheKey($context);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return array_slice($cached, 0, $limit);
        }

        $actions = $this->evaluate($context, $insights);

        Cache::put($cacheKey, $actions, self::CACHE_TTL);

        return array_slice($actions, 0, $limit);
    }

    /**
     * Get actions serialized for the frontend.
     *
     * @param  InsightResult[]  $insights
     */
    public function forWorkspace(AutomationContext $context, array $insights, int $limit = 5): array
    {
        $actions = $this->generate($context, $insights, $limit);

        return array_map(
            fn (AutomationAction $action): array => $action->toArray(),
            $actions
        );
    }

    /**
     * Get high/critical actions suitable for FAB prioritization.
     *
     * @param  InsightResult[]  $insights
     * @return AutomationAction[]
     */
    public function forFab(AutomationContext $context, array $insights, int $limit = 3): array
    {
        $actions = $this->generate($context, $insights, $limit + 5);

        // Only high/critical for FAB
        $urgent = array_filter(
            $actions,
            fn (AutomationAction $a): bool => in_array($a->severity, [
                AutomationAction::SEVERITY_CRITICAL,
                AutomationAction::SEVERITY_HIGH,
            ], true)
        );

        return array_slice(array_values($urgent), 0, $limit);
    }

    /**
     * Invalidate cached automation actions for a period.
     */
    public static function invalidateCache(int $periodId): void
    {
        Cache::forget(self::CACHE_PREFIX.$periodId);
    }

    /**
     * Run all rules and collect actions.
     *
     * @return AutomationAction[]
     */
    private function evaluate(AutomationContext $context, array $insights): array
    {
        $allActions = [];
        $seenRuleKeys = [];

        foreach ($this->rules as $rule) {
            $ruleActions = $rule->evaluate($context, $insights);

            foreach ($ruleActions as $action) {
                // Deduplicate: only keep the first (highest-severity) action per rule key
                $ruleKey = $action->meta['rule'] ?? $rule->ruleKey();
                if (isset($seenRuleKeys[$ruleKey])) {
                    // Keep the more severe one
                    $existing = $seenRuleKeys[$ruleKey];
                    if ($action->severityWeight() > $allActions[$existing]->severityWeight()) {
                        $allActions[$existing] = $action;
                    }

                    continue;
                }

                $seenRuleKeys[$ruleKey] = count($allActions);
                $allActions[] = $action;
            }
        }

        // Sort by severity descending
        usort($allActions, fn (AutomationAction $a, AutomationAction $b): int => $b->severityWeight() <=> $a->severityWeight()
        );

        return $allActions;
    }

    private function cacheKey(AutomationContext $context): string
    {
        $key = self::CACHE_PREFIX.$context->periodId;

        if ($context->groupId !== null) {
            $key .= ':group:'.$context->groupId;
        }

        if ($context->studentId !== null) {
            $key .= ':student:'.$context->studentId;
        }

        return $key;
    }
}
