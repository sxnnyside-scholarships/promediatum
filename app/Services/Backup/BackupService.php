<?php

namespace App\Services\Backup;

use App\Services\Desktop\DesktopPathResolver;
use Illuminate\Support\Carbon;

/**
 * Encrypted backup service for the Promediatum desktop application.
 *
 * Creates AES-256-CBC encrypted backups of the SQLite database in .pdbk format.
 * Supports: create, encrypt, restore, validate, list, prune.
 */
class BackupService
{
    /**
     * Backup file extension.
     */
    protected const EXTENSION = 'pdbk';

    /**
     * Encryption cipher (matches Laravel's default).
     */
    protected const CIPHER = 'aes-256-cbc';

    /**
     * Magic bytes header to identify .pdbk files.
     */
    protected const MAGIC_HEADER = 'PDBK';

    /**
     * Format version for forward-compatibility.
     */
    protected const FORMAT_VERSION = 1;

    public function __construct(
        protected DesktopPathResolver $pathResolver,
    ) {}

    /**
     * Create a new encrypted backup of the current database.
     *
     * @param  string|null  $password  User-supplied password (falls back to APP_KEY).
     * @return array{path: string, size: int, timestamp: string}
     *
     * @throws \RuntimeException If the database file doesn't exist or backup fails.
     */
    /**
     * Resolve the active SQLite database path.
     * Prioritizes the active database connection configuration, then the desktop path resolver.
     */
    public function getDatabasePath(): string
    {
        $desktopDb = $this->pathResolver->databasePath();
        if (file_exists($desktopDb)) {
            return $desktopDb;
        }

        try {
            if (function_exists('config')) {
                $configured = config('database.connections.sqlite.database');
                if (is_string($configured) && file_exists($configured)) {
                    return $configured;
                }
                if (is_string($configured) && $configured !== '') {
                    return $configured;
                }
            }
        } catch (\Throwable) {
            // Container not initialized (e.g. pure PHPUnit unit test)
        }

        return $desktopDb;
    }

    /**
     * Create an encrypted backup of the current database.
     *
     * @param  string|null  $password  Optional encryption password.
     * @return array{path: string, filename: string, size: int, timestamp: string}
     */
    public function create(?string $password = null): array
    {
        $dbPath = $this->getDatabasePath();

        if (! file_exists($dbPath)) {
            throw new \RuntimeException('Database file does not exist: '.$dbPath);
        }

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $unique = substr(bin2hex(random_bytes(3)), 0, 6);
        $filename = "promediatum_backup_{$timestamp}_{$unique}.".self::EXTENSION;

        $backupsDir = $this->pathResolver->backupsPath();
        if (! is_dir($backupsDir)) {
            mkdir($backupsDir, 0755, true);
        }
        $backupPath = $backupsDir.DIRECTORY_SEPARATOR.$filename;

        // Read the database
        $plaintext = file_get_contents($dbPath);

        if ($plaintext === false) {
            throw new \RuntimeException('Failed to read database file.');
        }

        // Encrypt and write
        $encrypted = $this->encrypt($plaintext, $password);
        $written = file_put_contents($backupPath, $encrypted);

        if ($written === false) {
            throw new \RuntimeException('Failed to write backup file.');
        }

        return [
            'path' => $backupPath,
            'filename' => $filename,
            'size' => $written,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Restore a database from an encrypted .pdbk backup file.
     *
     * @param  string  $backupPath  Absolute path to the .pdbk file.
     * @param  string|null  $password  Password used during backup creation.
     *
     * @throws \RuntimeException If decryption or validation fails.
     */
    public function restore(string $backupPath, ?string $password = null): bool
    {
        if (! file_exists($backupPath)) {
            throw new \RuntimeException('Backup file does not exist: '.$backupPath);
        }

        $encrypted = file_get_contents($backupPath);

        if ($encrypted === false) {
            throw new \RuntimeException('Failed to read backup file.');
        }

        // Validate the format before attempting decryption
        if (! $this->isValidFormat($encrypted)) {
            throw new \RuntimeException('Invalid backup file format.');
        }

        $plaintext = $this->decrypt($encrypted, $password);

        // Validate that the decrypted content looks like a SQLite database
        if (! $this->isSQLiteData($plaintext)) {
            throw new \RuntimeException('Decrypted data is not a valid SQLite database. Wrong password?');
        }

        $dbPath = $this->getDatabasePath();

        // Create a safety copy of the current database before restoring
        if (file_exists($dbPath)) {
            $safetyCopy = $dbPath.'.pre-restore.'.time();
            copy($dbPath, $safetyCopy);
        }

        $written = file_put_contents($dbPath, $plaintext);

        return $written !== false;
    }

    /**
     * Validate a backup file without restoring it.
     *
     * @param  string  $backupPath  Absolute path to the .pdbk file.
     * @param  string|null  $password  Password used during backup creation.
     * @return array{valid: bool, size: int, error?: string}
     */
    public function validate(string $backupPath, ?string $password = null): array
    {
        try {
            if (! file_exists($backupPath)) {
                return ['valid' => false, 'size' => 0, 'error' => 'File does not exist.'];
            }

            $encrypted = file_get_contents($backupPath);
            $size = strlen($encrypted);

            if (! $this->isValidFormat($encrypted)) {
                return ['valid' => false, 'size' => $size, 'error' => 'Invalid .pdbk format.'];
            }

            $plaintext = $this->decrypt($encrypted, $password);

            if (! $this->isSQLiteData($plaintext)) {
                return ['valid' => false, 'size' => $size, 'error' => 'Decrypted data is not valid SQLite.'];
            }

            return ['valid' => true, 'size' => $size];
        } catch (\Throwable $e) {
            return ['valid' => false, 'size' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the backups directory path.
     */
    public function getBackupsDirectory(): string
    {
        return $this->pathResolver->backupsPath();
    }

    /**
     * List all available backup files, sorted newest first.
     *
     * @return array<int, array{filename: string, path: string, size: int, created_at: string}>
     */
    public function list(): array
    {
        $dir = $this->pathResolver->backupsPath();

        if (! is_dir($dir)) {
            return [];
        }

        $pattern = $dir.DIRECTORY_SEPARATOR.'*.'.self::EXTENSION;
        $matches = glob($pattern) ?: [];

        $files = array_map(function (string $path) {
            return [
                'filename' => basename($path),
                'path' => $path,
                'size' => filesize($path),
                'created_at' => Carbon::createFromTimestamp(filemtime($path))->toIso8601String(),
            ];
        }, $matches);

        // Sort newest first
        usort($files, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return array_values($files);
    }

    /**
     * Delete old backups, keeping only the N most recent.
     *
     * @param  int  $keep  Number of recent backups to retain.
     * @return int Number of backups deleted.
     */
    public function prune(int $keep = 5): int
    {
        $backups = $this->list();
        $deleted = 0;

        // Keep the first $keep; delete the rest
        $toDelete = array_slice($backups, $keep);

        foreach ($toDelete as $backup) {
            if (@unlink($backup['path'])) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Encrypt data using AES-256-CBC with HMAC verification.
     *
     * Format: PDBK[version:1B][iv:16B][hmac:32B][ciphertext]
     */
    protected function encrypt(string $plaintext, ?string $password = null): string
    {
        $key = $this->deriveKey($password);
        $iv = random_bytes(16);

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed: '.openssl_error_string());
        }

        // HMAC for integrity verification (encrypt-then-MAC)
        $hmac = hash_hmac('sha256', $iv.$ciphertext, $key, true);

        // Pack: magic(4B) + version(1B) + iv(16B) + hmac(32B) + ciphertext
        return self::MAGIC_HEADER
            .chr(self::FORMAT_VERSION)
            .$iv
            .$hmac
            .$ciphertext;
    }

    /**
     * Decrypt a .pdbk backup payload.
     */
    protected function decrypt(string $data, ?string $password = null): string
    {
        $key = $this->deriveKey($password);

        // Parse: magic(4B) + version(1B) + iv(16B) + hmac(32B) + ciphertext
        $offset = strlen(self::MAGIC_HEADER) + 1; // Skip magic + version
        $iv = substr($data, $offset, 16);
        $offset += 16;
        $storedHmac = substr($data, $offset, 32);
        $offset += 32;
        $ciphertext = substr($data, $offset);

        // Verify HMAC integrity
        $computedHmac = hash_hmac('sha256', $iv.$ciphertext, $key, true);

        if (! hash_equals($storedHmac, $computedHmac)) {
            throw new \RuntimeException('Backup integrity check failed. The file may be corrupted or the password is wrong.');
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed: '.openssl_error_string());
        }

        return $plaintext;
    }

    /**
     * Derive an encryption key from a password or the application key.
     */
    protected function deriveKey(?string $password = null): string
    {
        $secret = $password ?? config('app.key');

        // If the app key starts with "base64:", decode it first
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        // PBKDF2 key derivation with a fixed salt (app-specific)
        return hash_pbkdf2(
            'sha256',
            $secret,
            'promediatum-backup-salt-v1',
            100000,
            32,
            true
        );
    }

    /**
     * Check if data has the valid .pdbk magic header.
     */
    protected function isValidFormat(string $data): bool
    {
        if (strlen($data) < strlen(self::MAGIC_HEADER) + 1 + 16 + 32) {
            return false;
        }

        return str_starts_with($data, self::MAGIC_HEADER);
    }

    /**
     * Check if data starts with the SQLite magic header.
     */
    protected function isSQLiteData(string $data): bool
    {
        return str_starts_with($data, 'SQLite format 3');
    }
}
