<?php

declare(strict_types=1);

namespace App\Services\Automation\Rules;

use App\Services\Automation\AutomationAction;
use App\Services\Automation\AutomationContext;
use App\Services\Automation\AutomationRule;
use App\Services\Insights\InsightResult;

/**
 * ConsecutiveAbsencesFollowUpRule — When a student has ≥3 consecutive
 * absences, suggest scheduling a follow-up.
 */
final class ConsecutiveAbsencesFollowUpRule extends AutomationRule
{
    public function ruleKey(): string
    {
        return 'consecutive_absences_followup';
    }

    public function evaluate(AutomationContext $context, array $insights): array
    {
        $actions = [];

        foreach ($insights as $insight) {
            if ($insight->type !== InsightResult::TYPE_ATTENDANCE) {
                continue;
            }

            // Only target the "consecutive absences" insights (not low-rate ones)
            $streak = $insight->meta['streak'] ?? 0;
            if ($streak < 3) {
                continue;
            }

            $severity = $streak >= 5
                ? AutomationAction::SEVERITY_CRITICAL
                : AutomationAction::SEVERITY_HIGH;

            $actions[] = new AutomationAction(
                type:        AutomationAction::TYPE_SUGGEST_FOLLOWUP,
                severity:    $severity,
                title:       __('automation.suggest_followup_title'),
                description: __('automation.suggest_followup_desc', ['count' => $streak]),
                icon:        'phone',
                route:       $insight->route ? null : 'observations.index',
                meta:        [
                    'student_id' => $insight->meta['student_id'] ?? null,
                    'group_id'   => $insight->meta['group_id'] ?? null,
                    'streak'     => $streak,
                    'rule'       => $this->ruleKey(),
                ],
            );
        }

        return $actions;
    }
}
