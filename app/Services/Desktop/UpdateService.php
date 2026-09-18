<?php

namespace App\Services\Desktop;

use App\Services\Backup\BackupService;
use Illuminate\Support\Facades\Log;
use Native\Laravel\Facades\Notification;

/**
 * UpdateService — Safe auto-update mechanism for the Promediatum desktop app.
 *
 * Flow:
 * 1. On app start, optionally check for updates (silent check).
 * 2. Compare current version vs latest GitHub Release.
 * 3. If newer version exists, notify user and offer update.
 * 4. Before applying: automatically create encrypted backup.
 * 5. Apply update via NativePHP's Electron autoUpdater.
 * 6. After restart: run safe migration flow.
 *
 * Safety: The update NEVER proceeds without a successful pre-update backup.
 * If migration fails post-update, the backup is restored automatically.
 */
class UpdateService
{
    public function __construct(
        protected BackupService $backupService,
        protected DesktopPathResolver $pathResolver,
    ) {}

    /**
     * Get the current application version.
     */
    public function currentVersion(): string
    {
        return config('version.version', '1.0.0');
    }

    /**
     * Get the current build number.
     */
    public function currentBuild(): int
    {
        return (int) config('version.build_number', 1);
    }

    /**
     * Get the release channel (stable / beta).
     */
    public function releaseChannel(): string
    {
        return config('version.release_channel', 'stable');
    }

    /**
     * Check if the NativePHP updater is enabled and configured.
     */
    public function isUpdaterEnabled(): bool
    {
        return (bool) config('nativephp.updater.enabled', false);
    }

    /**
     * Check for available updates via NativePHP's Electron updater.
     *
     * This delegates to NativePHP's built-in updater that uses
     * electron-updater under the hood. The check is non-blocking.
     *
     * @return array{available: bool, latest_version?: string, error?: string}
     */
    public function checkForUpdates(): array
    {
        if (! $this->isUpdaterEnabled()) {
            return [
                'available' => false,
                'error' => 'Updater is disabled.',
            ];
        }

        try {
            // NativePHP's electron updater handles the actual check
            // via the Electron autoUpdater API. We trigger it via Artisan.
            // In bundled mode, this will check the configured provider (GitHub).
            \Artisan::call('native:update:check');

            return [
                'available' => false, // The updater will fire events if available
                'current_version' => $this->currentVersion(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Update check failed: '.$e->getMessage());

            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a pre-update backup before applying any update.
     *
     * This is the critical safety mechanism — never apply an update
     * without a valid backup.
     *
     * @return array{success: bool, path?: string, error?: string}
     */
    public function createPreUpdateBackup(): array
    {
        try {
            $result = $this->backupService->create();

            // Validate the backup was created successfully
            $validation = $this->backupService->validate($result['path']);

            if (! $validation['valid']) {
                return [
                    'success' => false,
                    'error' => 'Pre-update backup created but validation failed: '
                        .($validation['error'] ?? 'unknown'),
                ];
            }

            Log::info('Pre-update backup created: '.$result['filename']);

            return [
                'success' => true,
                'path' => $result['path'],
                'filename' => $result['filename'],
            ];
        } catch (\Throwable $e) {
            Log::error('Pre-update backup failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Run the safe migration flow after an update.
     *
     * Steps:
     * 1. Auto-backup database before migration.
     * 2. Run pending migrations.
     * 3. If migration fails, restore the backup and report error.
     *
     * @return array{success: bool, migrated: bool, restored: bool, error?: string}
     */
    public function runSafeMigration(): array
    {
        // Step 1: Create a backup before migration
        $backup = $this->createPreUpdateBackup();

        if (! $backup['success']) {
            return [
                'success' => false,
                'migrated' => false,
                'restored' => false,
                'error' => 'Could not create pre-migration backup: '.($backup['error'] ?? 'unknown'),
            ];
        }

        // Step 2: Attempt migrations
        try {
            $exitCode = \Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);

            if ($exitCode !== 0) {
                throw new \RuntimeException(
                    'Migration exited with code '.$exitCode.': '.\Artisan::output()
                );
            }

            Log::info('Post-update migration completed successfully.');

            return [
                'success' => true,
                'migrated' => true,
                'restored' => false,
            ];
        } catch (\Throwable $e) {
            Log::error('Post-update migration failed: '.$e->getMessage());

            // Step 3: Restore backup on failure
            return $this->attemptRestore($backup['path'], $e->getMessage());
        }
    }

    /**
     * Attempt to restore a backup after a failed migration.
     */
    protected function attemptRestore(string $backupPath, string $originalError): array
    {
        try {
            $this->backupService->restore($backupPath);

            Log::info('Database restored from backup after failed migration.');

            $this->notifyUser(
                __('desktop.migration_failed_restored_title'),
                __('desktop.migration_failed_restored_message')
            );

            return [
                'success' => false,
                'migrated' => false,
                'restored' => true,
                'error' => $originalError,
            ];
        } catch (\Throwable $restoreError) {
            Log::critical('CRITICAL: Both migration and restore failed. '
                .'Migration error: '.$originalError
                .' | Restore error: '.$restoreError->getMessage());

            $this->notifyUser(
                __('desktop.critical_error_title'),
                __('desktop.critical_error_message')
            );

            return [
                'success' => false,
                'migrated' => false,
                'restored' => false,
                'error' => 'Migration failed: '.$originalError
                    .' | Restore also failed: '.$restoreError->getMessage(),
            ];
        }
    }

    /**
     * Get version information for display in the About screen.
     */
    public function versionInfo(): array
    {
        return [
            'version' => $this->currentVersion(),
            'build' => $this->currentBuild(),
            'channel' => $this->releaseChannel(),
            'release_date' => config('version.release_date', 'unknown'),
            'updater_enabled' => $this->isUpdaterEnabled(),
            'updater_provider' => config('nativephp.updater.default', 'none'),
        ];
    }

    /**
     * Send a native notification (safe wrapper).
     */
    protected function notifyUser(string $title, string $message): void
    {
        try {
            if (DesktopPathResolver::isDesktop()) {
                Notification::title($title)
                    ->message($message)
                    ->show();
            }
        } catch (\Throwable $e) {
            Log::warning('Notification failed: '.$e->getMessage());
        }
    }
}
