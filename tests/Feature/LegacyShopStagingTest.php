<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use ZipArchive;

class LegacyShopStagingTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/vira-legacy-stage-test-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }

        rmdir($this->directory);
        parent::tearDown();
    }

    public function test_it_stages_only_theme_admin_and_relevant_public_assets_from_nested_zip(): void
    {
        $archive = $this->makeArchive([
            'themes/DefaultTheme/src/resources/views/home.blade.php' => 'THEME VIEW',
            'resources/views/back/dashboard.blade.php' => 'ADMIN VIEW',
            'public/back/app-assets/css/main.css' => 'SOME CSS',
            'vendor/do-not-copy.php' => 'NOT ALLOWED',
            'app/Models/User.php' => 'NOT ALLOWED',
            'public/.htaccess' => 'NOT ALLOWED',
        ]);
        $target = $this->directory.'/stage';

        $run = $this->runStage($archive, '--apply', '--target='.$target);

        $this->assertTrue($run->isSuccessful(), $run->getErrorOutput().$run->getOutput());
        $this->assertSame('THEME VIEW', file_get_contents($target.'/themes/DefaultTheme/src/resources/views/home.blade.php'));
        $this->assertSame('ADMIN VIEW', file_get_contents($target.'/resources/views/back/dashboard.blade.php'));
        $this->assertSame('SOME CSS', file_get_contents($target.'/public/back/app-assets/css/main.css'));
        $this->assertFileDoesNotExist($target.'/vendor/do-not-copy.php');
        $this->assertFileDoesNotExist($target.'/app/Models/User.php');
        $this->assertFileDoesNotExist($target.'/public/.htaccess');
        $this->assertFileExists($target.'/manifest.json');
    }

    public function test_plan_does_not_copy_files_or_create_target(): void
    {
        $archive = $this->makeArchive([
            'themes/DefaultTheme/src/resources/views/home.blade.php' => 'VIEW',
        ]);
        $target = $this->directory.'/stage';

        $run = $this->runStage($archive, '--target='.$target);

        $this->assertTrue($run->isSuccessful(), $run->getErrorOutput().$run->getOutput());
        $this->assertStringContainsString('Plan:', $run->getOutput());
        $this->assertDirectoryDoesNotExist($target);
    }

    public function test_import_rejects_zip_traversal_without_writing_files(): void
    {
        $archive = $this->makeArchive([
            'themes/DefaultTheme/../../escape.php' => 'NOT ALLOWED',
        ]);
        $target = $this->directory.'/stage';

        $run = $this->runStage($archive, '--apply', '--target='.$target);

        $this->assertFalse($run->isSuccessful());
        $this->assertDirectoryDoesNotExist($target);
        $this->assertFileDoesNotExist($this->directory.'/escape.php');
    }

    public function test_import_refuses_to_overwrite_existing_staging_directory(): void
    {
        $archive = $this->makeArchive([
            'themes/DefaultTheme/src/resources/views/home.blade.php' => 'VIEW',
        ]);
        $target = $this->directory.'/stage';
        mkdir($target);
        file_put_contents($target.'/existing.txt', 'DO NOT OVERWRITE');

        $run = $this->runStage($archive, '--apply', '--target='.$target);

        $this->assertFalse($run->isSuccessful());
        $this->assertSame('DO NOT OVERWRITE', file_get_contents($target.'/existing.txt'));
    }

    private function makeArchive(array $files): string
    {
        $innerPath = $this->directory.'/inner-'.bin2hex(random_bytes(4)).'.zip';
        $inner = new ZipArchive;
        $this->assertTrue($inner->open($innerPath, ZipArchive::CREATE));
        foreach ($files as $path => $content) {
            $this->assertTrue($inner->addFromString($path, $content));
        }
        $this->assertTrue($inner->close());

        $outerPath = $this->directory.'/outer-'.bin2hex(random_bytes(4)).'.zip';
        $outer = new ZipArchive;
        $this->assertTrue($outer->open($outerPath, ZipArchive::CREATE));
        $this->assertTrue($outer->addFile($innerPath, 'main-1.19.8/script/webapp-v1.19.8.zip'));
        $this->assertTrue($outer->close());

        return $outerPath;
    }

    private function runStage(string $archive, string ...$options): Process
    {
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2).'/scripts/stage-laravel-shop.php',
            $archive,
            ...$options,
        ]);
        $process->run();

        return $process;
    }
}
