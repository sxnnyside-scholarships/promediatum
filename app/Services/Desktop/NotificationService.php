<?php

namespace App\Services\Desktop;

use Native\Laravel\Facades\Notification;

/**
 * Desktop notification service — wraps NativePHP's Notification facade
 * with a simple API and graceful fallback for non-desktop contexts.
 */
class NotificationService
{
    /**
     * Send a native desktop notification.
     *
     * @param  string  $title  Notification title.
     * @param  string  $message  Notification body text.
     */
    public function notify(string $title, string $message): void
    {
        if (! DesktopPathResolver::isDesktop()) {
            // Not running in NativePHP context — log instead
            logger()->info("[Notification] {$title}: {$message}");

            return;
        }

        try {
            Notification::title($title)
                ->message($message)
                ->show();
        } catch (\Throwable $e) {
            logger()->warning("Notification failed: {$e->getMessage()}");
        }
    }

    /**
     * Send an export-complete notification.
     */
    public function exportComplete(string $filename): void
    {
        $this->notify(
            __('desktop.notification_export_title'),
            __('desktop.notification_export_message', ['filename' => $filename])
        );
    }

    /**
     * Send a backup-complete notification.
     */
    public function backupComplete(string $filename): void
    {
        $this->notify(
            __('desktop.notification_backup_title'),
            __('desktop.notification_backup_message', ['filename' => $filename])
        );
    }

    /**
     * Send a backup-restore notification.
     */
    public function backupRestored(): void
    {
        $this->notify(
            __('desktop.notification_restore_title'),
            __('desktop.notification_restore_message')
        );
    }

    /**
     * Send an error notification.
     */
    public function error(string $message): void
    {
        $this->notify(
            __('desktop.notification_error_title'),
            $message
        );
    }
}
