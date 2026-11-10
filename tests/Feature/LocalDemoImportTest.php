<?php

namespace Tests\Feature;

use App\Support\DemoImportGuard;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LocalDemoImportTest extends TestCase
{
    public function test_guard_only_allows_the_exact_local_sqlite_file(): void
    {
        $folder = sys_get_temp_dir().'/vira-demo-guard-'.bin2hex(random_bytes(5));
        mkdir($folder);
        $main = $folder.'/database.sqlite';
        $other = $folder.'/other.sqlite';
        file_put_contents($main, '');
        file_put_contents($other, '');

        try {
            $this->assertTrue(DemoImportGuard::allows('local', 'sqlite', $main, $main));
            $this->assertFalse(DemoImportGuard::allows('production', 'sqlite', $main, $main));
            $this->assertFalse(DemoImportGuard::allows('testing', 'sqlite', $main, $main));
            $this->assertFalse(DemoImportGuard::allows('local', 'mysql', $main, $main));
            $this->assertFalse(DemoImportGuard::allows('local', 'sqlite', $other, $main));
            $this->assertFalse(DemoImportGuard::allows('local', 'sqlite', ':memory:', $main));
            $this->assertFalse(DemoImportGuard::allows('local', 'sqlite', $folder.'/missing.sqlite', $main));
        } finally {
            unlink($main);
            unlink($other);
            rmdir($folder);
        }
    }

    public function test_import_command_refuses_the_test_database_even_with_yes_flag(): void
    {
        $this->assertSame(1, Artisan::call('vira:demo:import', ['--yes' => true]));
    }
}
