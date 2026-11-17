<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase as UnitTestCase;
use Symfony\Component\Process\Process;

class LegacyThemeAssetPublishTest extends UnitTestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().'/vira-shop-publish-'.bin2hex(random_bytes(7));
        mkdir($this->workDir, 0700, true);
    }

    protected function tearDown(): void
    {
        $entries = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->workDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($entries as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($this->workDir);
        parent::tearDown();
    }

    public function test_dry_run_does_not_publish_anything(): void
    {
        $stage = $this->makeStage();
        $process = $this->runPublisher($stage);

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertStringContainsString('Plan:', $process->getOutput());
        $this->assertDirectoryDoesNotExist($this->workDir.'/public-theme');
        $this->assertDirectoryDoesNotExist($this->workDir.'/compat');
    }

    public function test_it_publishes_assets_and_an_adapted_original_layout_but_no_php_assets(): void
    {
        $stage = $this->makeStage();
        $process = $this->runPublisher($stage, '--apply');

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertSame('body { color: #000; }', file_get_contents($this->workDir.'/public-theme/css/main.css'));
        $this->assertFileDoesNotExist($this->workDir.'/public-theme/app.php');
        $this->assertStringContainsString(
            '@if (true)',
            file_get_contents($this->workDir.'/compat/layouts/master.blade.php')
        );
        $this->assertFileExists($this->workDir.'/public-theme/asset-manifest.json');
    }

    public function test_rejects_tampered_files_before_publication(): void
    {
        $stage = $this->makeStage();
        file_put_contents($stage.'/themes/DefaultTheme/src/resources/assets/css/main.css', 'modified');
        $process = $this->runPublisher($stage, '--apply');

        $this->assertFalse($process->isSuccessful());
        $this->assertDirectoryDoesNotExist($this->workDir.'/public-theme');
    }

    public function test_rejects_existing_public_output_without_overwriting(): void
    {
        $stage = $this->makeStage();
        mkdir($this->workDir.'/public-theme');
        file_put_contents($this->workDir.'/public-theme/keep.txt', 'safe');

        $process = $this->runPublisher($stage, '--apply');

        $this->assertFalse($process->isSuccessful());
        $this->assertSame('safe', file_get_contents($this->workDir.'/public-theme/keep.txt'));
    }

    private function makeStage(): string
    {
        $stage = $this->workDir.'/stage';
        $files = [
            'themes/DefaultTheme/src/resources/assets/css/main.css' => 'body { color: #000; }',
            'themes/DefaultTheme/src/resources/assets/app.php' => '<?php exit;',
            'themes/DefaultTheme/src/resources/views/layouts/master.blade.php' => '@if (config(\'app.debug\')) ORIGINAL @endif',
            'themes/DefaultTheme/src/resources/views/index.blade.php' => '@extends(\'front::layouts.master\')',
        ];
        $manifest = ['files' => []];

        foreach ($files as $name => $body) {
            $path = $stage.'/'.$name;
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0700, true);
            }
            file_put_contents($path, $body);
            $manifest['files'][] = ['path' => $name, 'size' => strlen($body), 'sha256' => hash('sha256', $body)];
        }

        file_put_contents($stage.'/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));

        return $stage;
    }

    private function runPublisher(string $stage, string ...$flags): Process
    {
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2).'/scripts/publish-laravel-shop-theme.php',
            '--stage='.$stage,
            '--public-dir='.$this->workDir.'/public-theme',
            '--compat-dir='.$this->workDir.'/compat',
            ...$flags,
        ]);
        $process->run();

        return $process;
    }
}
