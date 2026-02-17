<?php

namespace App\Providers;

use Native\Laravel\Facades\Menu;
use Native\Laravel\Facades\Window;
use Native\Laravel\Facades\Notification;
use Native\Laravel\Contracts\ProvidesPhpIni;
use App\Services\Desktop\DesktopPathResolver;
use App\Services\Desktop\UpdateService;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        $this->ensureDesktopStorage();
        $this->runSafeMigration();
        $this->openMainWindow();
        $this->registerMenu();
        $this->checkForUpdates();
    }

    /**
     * Open the main application window with Promediatum defaults.
     */
    protected function openMainWindow(): void
    {
        $window = Window::open('main')
            ->title('Promediatum')
            ->width(1280)
            ->height(800)
            ->minWidth(1024)
            ->minHeight(720)
            ->resizable()
            ->rememberState()
            ->preventLeaveDomain(true)
            ->suppressNewWindows()
            ->route('workspace');

        // Disable dev tools in production builds
        if (app()->environment('production')) {
            $window->hideDevTools();
        }
    }

    /**
     * Register the desktop application menu.
     *
     * Promediatum | File | View | Window | Help
     */
    protected function registerMenu(): void
    {
        $version = config('version.version', '1.0.0');
        $isProduction = app()->environment('production');

        $helpSubmenu = [
            Menu::label(__('menu.about') . ' v' . $version)->disabled(),
            Menu::separator(),
            Menu::route('backup.index', __('menu.check_updates')),
        ];

        // Only show dev tools in non-production environments
        if (! $isProduction) {
            $helpSubmenu[] = Menu::separator();
            $helpSubmenu[] = Menu::devTools(__('menu.dev_tools'));
        }

        Menu::create(
            // ── Promediatum app menu (macOS) ──
            Menu::app(),

            // ── File menu ──
            Menu::label(__('menu.file'))->submenu(
                Menu::route('settings.index', __('menu.settings'), 'CmdOrCtrl+,'),
                Menu::separator(),
                Menu::route('backup.index', __('menu.create_backup'), 'CmdOrCtrl+Shift+B'),
                Menu::route('backup.index', __('menu.restore_backup')),
                Menu::separator(),
                Menu::quit(),
            ),

            // ── View ──
            Menu::view(__('menu.view')),

            // ── Window ──
            Menu::window(__('menu.window')),

            // ── Help ──
            Menu::label(__('menu.help'))->submenu(...$helpSubmenu),
        );
    }

    /**
     * Ensure desktop storage directories exist on first launch.
     */
    protected function ensureDesktopStorage(): void
    {
        try {
            $resolver = app(DesktopPathResolver::class);
            $resolver->ensureDirectories();
        } catch (\Throwable $e) {
            logger()->warning('Desktop storage setup skipped: ' . $e->getMessage());
        }
    }

    /**
     * Run the safe migration flow (backup → migrate → restore on failure).
     *
     * On first launch: creates the database file and runs all migrations.
     * On update: creates a backup, runs pending migrations, restores on fail.
     */
    protected function runSafeMigration(): void
    {
        try {
            $resolver = app(DesktopPathResolver::class);
            $dbPath = $resolver->databasePath();
            $isFirstLaunch = !file_exists($dbPath);

            if ($isFirstLaunch) {
                // First launch: create DB file and run all migrations
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                touch($dbPath);

                \Artisan::call('migrate', [
                    '--force' => true,
                    '--no-interaction' => true,
                ]);

                Notification::title('Promediatum')
                    ->message(__('desktop.first_launch_ready'))
                    ->show();
            } else {
                // Existing install: use safe migration (backup → migrate → restore on fail)
                $updateService = app(UpdateService::class);
                $result = $updateService->runSafeMigration();

                if (!$result['success'] && $result['restored']) {
                    // Migration failed but backup was restored — warn user
                    Notification::title(__('desktop.migration_failed_restored_title'))
                        ->message(__('desktop.migration_failed_restored_message'))
                        ->show();
                } elseif (!$result['success'] && !$result['restored']) {
                    // Critical: both migration and restore failed
                    logger()->critical('Safe migration failed completely: ' . ($result['error'] ?? 'unknown'));
                }
            }
        } catch (\Throwable $e) {
            logger()->error('Boot migration flow failed: ' . $e->getMessage());
        }
    }

    /**
     * Silently check for updates on startup (if enabled).
     */
    protected function checkForUpdates(): void
    {
        try {
            if (!config('nativephp.updater.enabled', false)) {
                return;
            }

            $updateService = app(UpdateService::class);
            $updateService->checkForUpdates();
        } catch (\Throwable $e) {
            logger()->warning('Update check skipped: ' . $e->getMessage());
        }
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'max_execution_time' => '300',
            'upload_max_filesize' => '64M',
            'post_max_size' => '64M',
        ];
    }
}
