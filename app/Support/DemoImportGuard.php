<?php

namespace App\Support;

final class DemoImportGuard
{
    public static function allows(string $environment, string $driver, string $database, string $expected): bool
    {
        if ($environment !== 'local' || $driver !== 'sqlite' || $database === ':memory:') {
            return false;
        }

        $actualPath = realpath($database);
        $expectedPath = realpath($expected);

        if ($actualPath === false || $expectedPath === false) {
            return false;
        }

        return DIRECTORY_SEPARATOR === '\\'
            ? strcasecmp($actualPath, $expectedPath) === 0
            : $actualPath === $expectedPath;
    }
}
