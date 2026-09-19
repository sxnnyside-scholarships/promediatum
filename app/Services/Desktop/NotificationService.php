<?php

namespace App\Services\Desktop;

use Illuminate\Support\Facades\Log;

/**
 * Desktop notification service — provides logging and dispatch
 * with a clean API for background desktop events.
 */
class NotificationService
{
    /**
     * Send or record a notification.
     *
     * @param  string  $title  Notification title.
     * @param  string  $message  Notification body text.
     */
    public function notify(string $title, string $message): void
    {
        Log::info("[Notification] {$title}: {$message}");
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
