<?php

namespace Tests\Unit;

use App\Services\Backup\BackupService;
use App\Services\Desktop\DesktopPathResolver;
use PHPUnit\Framework\TestCase;

class BackupServiceTest extends TestCase
{
    protected string $tempDir;
    protected BackupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/promediatum_test_' . uniqid();
        mkdir($this->tempDir . '/database', 0755, true);
        mkdir($this->tempDir . '/backups', 0755, true);

        // Create a mock resolver that uses temporary paths
        $resolver = $this->createMock(DesktopPathResolver::class);
        $resolver->method('databasePath')->willReturn($this->tempDir . '/database/database.sqlite');
        $resolver->method('databaseDir')->willReturn($this->tempDir . '/database');
        $resolver->method('backupsPath')->willReturn($this->tempDir . '/backups');

        $this->service = new BackupService($resolver);
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    protected function createFakeSQLiteDB(): string
    {
        $dbPath = $this->tempDir . '/database/database.sqlite';
        // SQLite files always start with this magic header
        file_put_contents($dbPath, "SQLite format 3\x00" . str_repeat("\x00", 100));
        return $dbPath;
    }

    public function test_create_backup_produces_pdbk_file(): void
    {
        // Temporarily set APP_KEY for test
        $origKey = $_ENV['APP_KEY'] ?? null;

        $this->createFakeSQLiteDB();

        $result = $this->service->create('test-password-123');

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('size', $result);
        $this->assertStringEndsWith('.pdbk', $result['filename']);
        $this->assertFileExists($result['path']);
        $this->assertGreaterThan(0, $result['size']);
    }

    public function test_backup_starts_with_magic_header(): void
    {
        $this->createFakeSQLiteDB();
        $result = $this->service->create('test-pw');

        $content = file_get_contents($result['path']);
        $this->assertStringStartsWith('PDBK', $content);
    }

    public function test_validate_accepts_valid_backup(): void
    {
        $this->createFakeSQLiteDB();
        $result = $this->service->create('my-secret');

        $validation = $this->service->validate($result['path'], 'my-secret');
        $this->assertTrue($validation['valid']);
    }

    public function test_validate_rejects_wrong_password(): void
    {
        $this->createFakeSQLiteDB();
        $result = $this->service->create('correct-password');

        $validation = $this->service->validate($result['path'], 'wrong-password');
        $this->assertFalse($validation['valid']);
    }

    public function test_validate_rejects_corrupted_file(): void
    {
        $fakePath = $this->tempDir . '/backups/fake.pdbk';
        file_put_contents($fakePath, 'not a real backup');

        $validation = $this->service->validate($fakePath, 'any');
        $this->assertFalse($validation['valid']);
    }

    public function test_validate_reports_missing_file(): void
    {
        $validation = $this->service->validate('/nonexistent/path.pdbk');
        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('does not exist', $validation['error']);
    }

    public function test_create_and_restore_round_trip(): void
    {
        $dbPath = $this->createFakeSQLiteDB();
        $original = file_get_contents($dbPath);

        // Create backup
        $result = $this->service->create('round-trip-pw');

        // Modify the DB (simulate changes)
        file_put_contents($dbPath, "SQLite format 3\x00" . str_repeat("\xFF", 100));
        $modified = file_get_contents($dbPath);
        $this->assertNotEquals($original, $modified);

        // Restore from backup
        $restored = $this->service->restore($result['path'], 'round-trip-pw');
        $this->assertTrue($restored);

        // Verify the original content is back
        $afterRestore = file_get_contents($dbPath);
        $this->assertEquals($original, $afterRestore);
    }

    public function test_restore_rejects_wrong_password(): void
    {
        $this->createFakeSQLiteDB();
        $result = $this->service->create('correct');

        $this->expectException(\RuntimeException::class);
        $this->service->restore($result['path'], 'incorrect');
    }

    public function test_list_returns_empty_array_when_no_backups(): void
    {
        $list = $this->service->list();
        $this->assertIsArray($list);
        $this->assertEmpty($list);
    }

    public function test_list_returns_backups_sorted_newest_first(): void
    {
        $this->createFakeSQLiteDB();

        // Create two backups with a small delay
        $first = $this->service->create('pw');
        sleep(1);
        $second = $this->service->create('pw');

        $list = $this->service->list();
        $this->assertCount(2, $list);
        // Newest first
        $this->assertEquals($second['filename'], $list[0]['filename']);
    }

    public function test_prune_keeps_specified_number_of_backups(): void
    {
        $this->createFakeSQLiteDB();

        // Create 4 backups (unique filenames via random suffix)
        for ($i = 0; $i < 4; $i++) {
            $this->service->create('pw');
        }

        $list = $this->service->list();
        $this->assertCount(4, $list);

        $deleted = $this->service->prune(2);
        $this->assertEquals(2, $deleted);
        $this->assertCount(2, $this->service->list());
    }

    public function test_create_throws_when_no_database(): void
    {
        // Don't create the database file
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not exist');
        $this->service->create('pw');
    }
}
