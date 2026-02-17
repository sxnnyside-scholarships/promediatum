<?php

declare(strict_types=1);

namespace App\Services\Automation\Rules;

use App\Services\Automation\AutomationAction;
use App\Services\Automation\AutomationContext;
use App\Services\Automation\AutomationRule;
use App\Services\Insights\InsightResult;

/**
 * HighRiskObservationRule — When a student has a high/critical risk score,
 * suggest creating an observation for follow-up.
 */
final class HighRiskObservationRule extends AutomationRule
{
    public function ruleKey(): string
    {
        return 'high_risk_observation';
    }

    public function evaluate(AutomationContext $context, array $insights): array
    {
        $actions = [];

        foreach ($insights as $insight) {
            if ($insight->type !== InsightResult::TYPE_RISK) {
                continue;
            }

            if (! in_array($insight->severity, [InsightResult::SEVERITY_HIGH, InsightResult::SEVERITY_CRITICAL], true)) {
                continue;
            }

            $studentId = $insight->meta['student_id'] ?? null;
            $groupId   = $insight->meta['group_id'] ?? null;
            $score     = $insight->meta['score'] ?? 0;

            $severity = $insight->severity === InsightResult::SEVERITY_CRITICAL
                ? AutomationAction::SEVERITY_CRITICAL
                : AutomationAction::SEVERITY_HIGH;

            $actions[] = new AutomationAction(
                type:        AutomationAction::TYPE_SUGGEST_OBSERVATION,
                severity:    $severity,
                title:       __('automation.suggest_observation_title'),
                description: __('automation.suggest_observation_desc', ['score' => $score]),
                icon:        'book',
                route:       'observations.index',
                meta:        [
                    'student_id' => $studentId,
                    'group_id'   => $groupId,
                    'rule'       => $this->ruleKey(),
                ],
            );
        }

        return $actions;
    }
}
