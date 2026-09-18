<?php

declare(strict_types=1);

namespace App\Services\Insights;

/**
 * InsightResult — Immutable value object representing a single insight.
 *
 * Every insight returned by the engine carries its type, severity,
 * human-readable message, a suggested action, and an optional route
 * so the frontend can link directly to the relevant page.
 */
final class InsightResult
{
    /** Severity constants in descending priority. */
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_LOW = 'low';

    /** Type constants. */
    public const TYPE_RISK = 'risk';

    public const TYPE_TREND = 'trend';

    public const TYPE_ATTENDANCE = 'attendance';

    public const TYPE_OBSERVATION = 'observation';

    /** Severity weight map for sorting (higher = more urgent). */
    private const SEVERITY_WEIGHTS = [
        self::SEVERITY_CRITICAL => 4,
        self::SEVERITY_HIGH => 3,
        self::SEVERITY_MEDIUM => 2,
        self::SEVERITY_LOW => 1,
    ];

    public function __construct(
        public readonly string $type,
        public readonly string $severity,
        public readonly string $message,
        public readonly string $suggestedAction,
        public readonly ?string $route = null,
        public readonly ?array $meta = null,
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
     *
     * @return array{type: string, severity: string, message: string, suggested_action: string, route: ?string, meta: ?array}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'message' => $this->message,
            'suggested_action' => $this->suggestedAction,
            'route' => $this->route,
            'meta' => $this->meta,
        ];
    }
}
