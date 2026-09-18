<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Automation\AutomationAction;
use Illuminate\Support\Facades\Cache;

/**
 * NotificationService — Manages in-app notification state for automation actions.
 *
 * Rules:
 *  - Only high/critical severity actions produce notifications
 *  - No duplicate notifications (deduplication by rule key + context)
 *  - 24-hour cooldown per rule type to prevent notification fatigue
 *  - Uses file/database cache for persistence (no external dependencies)
 */
final class NotificationService
{
    /** Cooldown duration in seconds (24 hours). */
    private const COOLDOWN_SECONDS = 86400;

    /** Cache prefix for cooldown tracking. */
    private const COOLDOWN_PREFIX = 'notification:cooldown:';

    /** Cache prefix for notification storage. */
    private const STORAGE_PREFIX = 'notifications:user:';

    /** Max notifications stored per user. */
    private const MAX_STORED = 20;

    /**
     * Process automation actions and emit notifications for high/critical severity.
     *
     * @param  AutomationAction[]  $actions
     * @return int Number of new notifications emitted
     */
    public function processActions(array $actions, int $userId): int
    {
        $emitted = 0;

        foreach ($actions as $action) {
            // Only notify for high/critical
            if (! in_array($action->severity, [
                AutomationAction::SEVERITY_CRITICAL,
                AutomationAction::SEVERITY_HIGH,
            ], true)) {
                continue;
            }

            $ruleKey = $action->meta['rule'] ?? $action->type;
            $contextKey = $this->contextKey($ruleKey, $action->meta ?? []);

            // Check cooldown
            if ($this->isOnCooldown($contextKey)) {
                continue;
            }

            // Emit notification
            $this->store($userId, $action);
            $this->setCooldown($contextKey);
            $emitted++;
        }

        return $emitted;
    }

    /**
     * Get unread notifications for a user.
     *
     * @return array<int, array{type: string, severity: string, title: string, description: string, icon: string, route: ?string, created_at: string, read: bool}>
     */
    public function getNotifications(int $userId, int $limit = 10): array
    {
        $notifications = Cache::get(self::STORAGE_PREFIX.$userId, []);

        return array_slice($notifications, 0, $limit);
    }

    /**
     * Count unread notifications.
     */
    public function unreadCount(int $userId): int
    {
        $notifications = Cache::get(self::STORAGE_PREFIX.$userId, []);

        return count(array_filter($notifications, fn (array $n): bool => ! $n['read']));
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllRead(int $userId): void
    {
        $notifications = Cache::get(self::STORAGE_PREFIX.$userId, []);

        foreach ($notifications as &$notification) {
            $notification['read'] = true;
        }

        Cache::put(self::STORAGE_PREFIX.$userId, $notifications, self::COOLDOWN_SECONDS * 7);
    }

    /**
     * Clear all notifications for a user.
     */
    public function clear(int $userId): void
    {
        Cache::forget(self::STORAGE_PREFIX.$userId);
    }

    /**
     * Store a notification for a user.
     */
    private function store(int $userId, AutomationAction $action): void
    {
        $notifications = Cache::get(self::STORAGE_PREFIX.$userId, []);

        array_unshift($notifications, [
            'type' => $action->type,
            'severity' => $action->severity,
            'title' => $action->title,
            'description' => $action->description,
            'icon' => $action->icon,
            'route' => $action->route,
            'created_at' => now()->toIso8601String(),
            'read' => false,
        ]);

        // Trim to max stored
        $notifications = array_slice($notifications, 0, self::MAX_STORED);

        // Store for 7 days
        Cache::put(self::STORAGE_PREFIX.$userId, $notifications, self::COOLDOWN_SECONDS * 7);
    }

    /**
     * Check if a rule+context combination is on cooldown.
     */
    private function isOnCooldown(string $contextKey): bool
    {
        return Cache::has(self::COOLDOWN_PREFIX.$contextKey);
    }

    /**
     * Set cooldown for a rule+context combination.
     */
    private function setCooldown(string $contextKey): void
    {
        Cache::put(self::COOLDOWN_PREFIX.$contextKey, true, self::COOLDOWN_SECONDS);
    }

    /**
     * Generate a unique context key for deduplication.
     */
    private function contextKey(string $ruleKey, array $meta): string
    {
        $parts = [$ruleKey];

        if (isset($meta['student_id'])) {
            $parts[] = 'stu'.$meta['student_id'];
        }
        if (isset($meta['group_id'])) {
            $parts[] = 'grp'.$meta['group_id'];
        }
        if (isset($meta['period_id'])) {
            $parts[] = 'per'.$meta['period_id'];
        }

        return implode(':', $parts);
    }
}
