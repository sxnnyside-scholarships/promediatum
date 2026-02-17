<?php

namespace App\Http\Controllers;

use App\Services\Backup\BackupService;
use App\Services\Desktop\DesktopPathResolver;
use App\Services\Desktop\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Validate that a backup path is within the allowed backups directory
     * and has the correct extension. Prevents path traversal attacks.
     */
    private function validateBackupPath(string $path): string
    {
        $resolved = realpath($path);
        $backupsDir = realpath($this->backupService->getBackupsDirectory());

        // For restore/validate: file must exist, be inside backups dir, and have .pdbk extension
        if ($resolved === false
            || $backupsDir === false
            || ! str_starts_with($resolved, $backupsDir . DIRECTORY_SEPARATOR)
            || ! str_ends_with($resolved, '.pdbk')
        ) {
            abort(403, 'Invalid backup path.');
        }

        return $resolved;
    }

    /**
     * Display the backup management page.
     */
    public function index()
    {
        return Inertia::render('Backups/Index', [
            'backups' => $this->backupService->list(),
        ]);
    }

    /**
     * Create a new backup.
     */
    public function store(Request $request)
    {
        $request->validate([
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        try {
            $result = $this->backupService->create($request->input('password'));

            $this->notificationService->backupComplete($result['filename']);

            return back()->with('success', __('backup.created_successfully'));
        } catch (\Throwable $e) {
            return back()->with('error', __('backup.creation_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Restore from a backup file.
     */
    public function restore(Request $request)
    {
        $request->validate([
            'backup_path' => ['required', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        try {
            $path = $this->validateBackupPath($request->input('backup_path'));

            $this->backupService->restore(
                $path,
                $request->input('password')
            );

            $this->notificationService->backupRestored();

            return back()->with('success', __('backup.restored_successfully'));
        } catch (\Throwable $e) {
            return back()->with('error', __('backup.restore_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Validate a backup file.
     */
    public function validate(Request $request)
    {
        $request->validate([
            'backup_path' => ['required', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        $path = $this->validateBackupPath($request->input('backup_path'));

        $result = $this->backupService->validate(
            $path,
            $request->input('password')
        );

        return back()->with('validation_result', $result);
    }

    /**
     * Delete a specific backup.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'backup_path' => ['required', 'string'],
        ]);

        $path = $this->validateBackupPath($request->input('backup_path'));

        unlink($path);
        return back()->with('success', __('backup.deleted_successfully'));
    }

    /**
     * Prune old backups, keeping only the most recent N.
     */
    public function prune(Request $request)
    {
        $keep = $request->input('keep', 5);
        $deleted = $this->backupService->prune($keep);

        return back()->with('success', __('backup.pruned_successfully', ['count' => $deleted]));
    }

    /**
     * Download a backup file.
     */
    public function download(Request $request)
    {
        $path = $this->validateBackupPath($request->input('backup_path'));

        return response()->download($path);
    }
}
