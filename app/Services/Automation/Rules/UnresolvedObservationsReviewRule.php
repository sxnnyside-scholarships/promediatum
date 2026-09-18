<?php

declare(strict_types=1);

namespace App\Services\Automation\Rules;

use App\Services\Automation\AutomationAction;
use App\Services\Automation\AutomationContext;
use App\Services\Automation\AutomationRule;

/**
 * UnresolvedObservationsReviewRule — When >5 unresolved observations exist,
 * suggest a batch review.
 */
final class UnresolvedObservationsReviewRule extends AutomationRule
{
    /** Threshold for suggesting review. */
    private const THRESHOLD = 5;

    public function ruleKey(): string
    {
        return 'unresolved_observations_review';
    }

    public function evaluate(AutomationContext $context, array $insights): array
    {
        if ($context->pendingObservations <= self::THRESHOLD) {
            return [];
        }

        $severity = $context->pendingObservations >= 10
            ? AutomationAction::SEVERITY_HIGH
            : AutomationAction::SEVERITY_MEDIUM;

        return [
            new AutomationAction(
                type: AutomationAction::TYPE_SUGGEST_REVIEW,
                severity: $severity,
                title: __('automation.suggest_review_title'),
                description: __('automation.suggest_review_desc', ['count' => $context->pendingObservations]),
                icon: 'clipboard',
                route: 'observations.index',
                meta: [
                    'pending_count' => $context->pendingObservations,
                    'rule' => $this->ruleKey(),
                ],
            ),
        ];
    }
}
