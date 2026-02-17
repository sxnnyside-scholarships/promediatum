<?php

declare(strict_types=1);

namespace App\Services\Automation;

/**
 * AutomationAction — Immutable value object representing a suggested action.
 *
 * Produced by the AutomationEngine when automation rules fire.
 * Actions are suggestions only — the system never auto-executes destructive operations.
 */
final class AutomationAction
{
    /** Action type constants. */
    public const TYPE_SUGGEST_OBSERVATION = 'suggest_observation';
    public const TYPE_SUGGEST_FOLLOWUP   = 'suggest_followup';
    public const TYPE_SUGGEST_EXPORT     = 'suggest_export';
    public const TYPE_SUGGEST_REVIEW     = 'suggest_review';

    /** Severity constants (mirrors InsightResult). */
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH     = 'high';
    public const SEVERITY_MEDIUM   = 'medium';
    public const SEVERITY_LOW      = 'low';

    /** Severity weight map for sorting. */
    private const SEVERITY_WEIGHTS = [
        self::SEVERITY_CRITICAL => 4,
        self::SEVERITY_HIGH     => 3,
        self::SEVERITY_MEDIUM   => 2,
        self::SEVERITY_LOW      => 1,
    ];

    public function __construct(
        public readonly string  $type,
        public readonly string  $severity,
        public readonly string  $title,
        public readonly string  $description,
        public readonly string  $icon,
        public readonly ?string $route = null,
        public readonly ?array  $routeParams = null,
        public readonly ?array  $meta = null,
    ) {}

    /**
     * Numeric weight for sorting by severity descending.
     */
    public function severityWeight(): int
    {
        return self::SEVERITY_WEIGHTS[$this->severity] ?? 0;
    }

    /**
     * Serialize to a frontend-friendly array.
     */
    public function toArray(): array
    {
        return [
            'type'         => $this->type,
            'severity'     => $this->severity,
            'title'        => $this->title,
            'description'  => $this->description,
            'icon'         => $this->icon,
            'route'        => $this->route,
            'route_params' => $this->routeParams,
            'meta'         => $this->meta,
        ];
    }
}
