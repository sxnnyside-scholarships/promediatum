<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Services\Insights\InsightResult;

/**
 * AutomationRule — Defines a single automation rule.
 *
 * Each rule evaluates InsightResults and/or the AutomationContext
 * to determine whether to produce AutomationActions.
 */
abstract class AutomationRule
{
    /**
     * Unique identifier for this rule (used for notification cooldown dedup).
     */
    abstract public function ruleKey(): string;

    /**
     * Evaluate the rule against the given context and insights.
     *
     * @param  InsightResult[]  $insights
     * @return AutomationAction[]
     */
    abstract public function evaluate(AutomationContext $context, array $insights): array;
}
