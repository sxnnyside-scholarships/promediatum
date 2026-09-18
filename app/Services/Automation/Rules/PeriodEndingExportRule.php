<?php

declare(strict_types=1);

namespace App\Services\Automation\Rules;

use App\Services\Automation\AutomationAction;
use App\Services\Automation\AutomationContext;
use App\Services\Automation\AutomationRule;

/**
 * PeriodEndingExportRule — When the active period ends in < 7 days,
 * suggest running an export to back up academic data.
 */
final class PeriodEndingExportRule extends AutomationRule
{
    /** Alert threshold in days. */
    private const THRESHOLD_DAYS = 7;

    public function ruleKey(): string
    {
        return 'period_ending_export';
    }

    public function evaluate(AutomationContext $context, array $insights): array
    {
        if ($context->daysRemaining === null) {
            return [];
        }

        if ($context->daysRemaining >= self::THRESHOLD_DAYS) {
            return [];
        }

        $severity = $context->daysRemaining <= 2
            ? AutomationAction::SEVERITY_HIGH
            : AutomationAction::SEVERITY_MEDIUM;

        return [
            new AutomationAction(
                type: AutomationAction::TYPE_SUGGEST_EXPORT,
                severity: $severity,
                title: __('automation.suggest_export_title'),
                description: __('automation.suggest_export_desc', ['days' => $context->daysRemaining]),
                icon: 'download',
                route: 'exports.history',
                meta: [
                    'period_id' => $context->periodId,
                    'days_remaining' => $context->daysRemaining,
                    'rule' => $this->ruleKey(),
                ],
            ),
        ];
    }
}
