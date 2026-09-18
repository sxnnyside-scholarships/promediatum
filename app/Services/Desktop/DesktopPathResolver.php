<?php

namespace App\Services\Desktop;

/**
 * OS-aware path resolver for desktop application storage.
 *
 * Resolves application data paths based on the host operating system:
 * - macOS:   ~/Library/Application Support/Promediatum/
 * - Windows: %AppData%/Promediatum/
 * - Linux:   ~/.local/share/Promediatum/
 *
 * Subdirectories: database/, storage/, exports/, backups/, logs/
 */
class DesktopPathResolver
{
    protected string $appName = 'Promediatum';

    /**
     * Required subdirectories inside the application data folder.
     */
    protected array $subdirectories = [
        'database',
        'storage',
        'exports',
        'backups',
        'logs',
    ];

    /**
     * Get the base application data directory for the current OS.
     */
    public function basePath(): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => $this->macOSPath(),
            'Windows' => $this->windowsPath(),
            default => $this->linuxPath(), // Linux / BSD / other
        };
    }

    /**
     * macOS: ~/Library/Application Support/Promediatum/
     */
    protected function macOSPath(): string
    {
        $home = $_SERVER['HOME'] ?? getenv('HOME') ?: '/tmp';

        return $home.'/Library/Application Support/'.$this->appName;
    }

    /**
     * Windows: %AppData%/Promediatum/
     */
    protected function windowsPath(): string
    {
        $appData = getenv('APPDATA') ?: (getenv('USERPROFILE').'\\AppData\\Roaming');

        return $appData.'\\'.$this->appName;
    }

    /**
     * Linux: ~/.local/share/Promediatum/
     */
    protected function linuxPath(): string
    {
        $home = $_SERVER['HOME'] ?? getenv('HOME') ?: '/tmp';
        $xdgData = getenv('XDG_DATA_HOME') ?: ($home.'/.local/share');

        return $xdgData.'/'.$this->appName;
    }

    /**
     * Get the database directory path.
     */
    public function databaseDir(): string
    {
        return $this->basePath().DIRECTORY_SEPARATOR.'database';
    }

    /**
     * Get the full path to the SQLite database file.
     */
    public function databasePath(): string
    {
        return $this->databaseDir().DIRECTORY_SEPARATOR.'database.sqlite';
    }

    /**
     * Get the storage directory path.
     */
    public function storagePath(): string
    {
        return $this->basePath().DIRECTORY_SEPARATOR.'storage';
    }

    /**
     * Get the exports directory path.
     */
    public function exportsPath(): string
    {
        return $this->basePath().DIRECTORY_SEPARATOR.'exports';
    }

    /**
     * Get the backups directory path.
     */
    public function backupsPath(): string
    {
        return $this->basePath().DIRECTORY_SEPARATOR.'backups';
    }

    /**
     * Get the logs directory path.
     */
    public function logsPath(): string
    {
        return $this->basePath().DIRECTORY_SEPARATOR.'logs';
    }

    /**
     * Ensure all required directories exist.
     *
     * @return array List of directories that were created.
     */
    public function ensureDirectories(): array
    {
        $created = [];

        foreach ($this->subdirectories as $subdir) {
            $path = $this->basePath().DIRECTORY_SEPARATOR.$subdir;

            if (! is_dir($path)) {
                if (! mkdir($path, 0755, true) && ! is_dir($path)) {
                    throw new \RuntimeException("Failed to create directory: {$path}");
                }
                $created[] = $path;
            }
        }

        return $created;
    }

    /**
     * Check if the application has been initialized (database exists).
     */
    public function isFirstLaunch(): bool
    {
        return ! file_exists($this->databasePath());
    }

    /**
     * Check if we're running inside a NativePHP desktop context.
     */
    public static function isDesktop(): bool
    {
        // Check via environment first (no container dependency)
        if (getenv('NATIVEPHP_RUNNING')) {
            return true;
        }

        // Check via config if the application container is available
        try {
            return config('nativephp-internal.running', false);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get a summary of all resolved paths (useful for diagnostics).
     */
    public function diagnostics(): array
    {
        return [
            'os_family' => PHP_OS_FAMILY,
            'base_path' => $this->basePath(),
            'database' => $this->databasePath(),
            'storage' => $this->storagePath(),
            'exports' => $this->exportsPath(),
            'backups' => $this->backupsPath(),
            'logs' => $this->logsPath(),
            'is_first_launch' => $this->isFirstLaunch(),
            'is_desktop' => static::isDesktop(),
        ];
    }
}
