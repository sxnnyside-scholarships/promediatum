<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Services\Desktop\DesktopPathResolver;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Observation;
use App\Observers\AttendanceObserver;
use App\Observers\GradeObserver;
use App\Observers\ObservationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register DesktopPathResolver as a singleton
        $this->app->singleton(DesktopPathResolver::class, function () {
            return new DesktopPathResolver();
        });

        // When running as a desktop app, override storage & database paths
        if (DesktopPathResolver::isDesktop()) {
            $this->configureDesktopPaths();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Register model observers for insight cache invalidation
        Grade::observe(GradeObserver::class);
        Attendance::observe(AttendanceObserver::class);
        Observation::observe(ObservationObserver::class);
    }

    /**
     * Configure application paths for the desktop (NativePHP) context.
     * Overrides database, storage, exports, and log paths to use the
     * OS-specific application data directory.
     */
    protected function configureDesktopPaths(): void
    {
        $resolver = $this->app->make(DesktopPathResolver::class);

        // Override SQLite database path
        config([
            'database.connections.sqlite.database' => $resolver->databasePath(),
        ]);

        // Override filesystem disks to use desktop exports directory
        config([
            'filesystems.disks.exports' => [
                'driver' => 'local',
                'root' => $resolver->exportsPath(),
            ],
        ]);

        // Override log channel to use desktop logs directory
        config([
            'logging.channels.desktop' => [
                'driver' => 'single',
                'path' => $resolver->logsPath() . '/promediatum.log',
                'level' => 'debug',
            ],
            'logging.default' => 'desktop',
        ]);
    }
}
