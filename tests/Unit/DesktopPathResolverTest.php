<?php

namespace Tests\Unit;

use App\Services\Desktop\DesktopPathResolver;
use PHPUnit\Framework\TestCase;

class DesktopPathResolverTest extends TestCase
{
    protected DesktopPathResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DesktopPathResolver();
    }

    public function test_base_path_returns_string(): void
    {
        $path = $this->resolver->basePath();
        $this->assertIsString($path);
        $this->assertStringContainsString('Promediatum', $path);
    }

    public function test_database_path_ends_with_sqlite(): void
    {
        $path = $this->resolver->databasePath();
        $this->assertStringEndsWith('database.sqlite', $path);
    }

    public function test_database_dir_is_subdirectory_of_base(): void
    {
        $base = $this->resolver->basePath();
        $dbDir = $this->resolver->databaseDir();
        $this->assertStringStartsWith($base, $dbDir);
    }

    public function test_all_paths_contain_promediatum(): void
    {
        $this->assertStringContainsString('Promediatum', $this->resolver->storagePath());
        $this->assertStringContainsString('Promediatum', $this->resolver->exportsPath());
        $this->assertStringContainsString('Promediatum', $this->resolver->backupsPath());
        $this->assertStringContainsString('Promediatum', $this->resolver->logsPath());
    }

    public function test_diagnostics_returns_expected_keys(): void
    {
        $diag = $this->resolver->diagnostics();
        $this->assertArrayHasKey('os_family', $diag);
        $this->assertArrayHasKey('base_path', $diag);
        $this->assertArrayHasKey('database', $diag);
        $this->assertArrayHasKey('storage', $diag);
        $this->assertArrayHasKey('exports', $diag);
        $this->assertArrayHasKey('backups', $diag);
        $this->assertArrayHasKey('logs', $diag);
        $this->assertArrayHasKey('is_first_launch', $diag);
        $this->assertArrayHasKey('is_desktop', $diag);
    }

    public function test_macos_path_format(): void
    {
        if (PHP_OS_FAMILY !== 'Darwin') {
            $this->markTestSkipped('macOS only test');
        }

        $path = $this->resolver->basePath();
        $this->assertStringContainsString('Library/Application Support/Promediatum', $path);
    }
}
