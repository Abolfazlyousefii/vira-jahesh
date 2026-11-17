<?php

/**
 * Publish only the original DefaultTheme's static assets from a verified
 * private Laravel Shop staging directory. The source PHP is never published.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$stage = $root.'/storage/app/private/legacy-shop-1.19.8';
$publicDir = $root.'/public/legacy-shop-theme';
$compatDir = $stage.'/compat/views';
$apply = false;

foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--apply') {
        $apply = true;
    } elseif (str_starts_with($argument, '--stage=')) {
        $stage = substr($argument, 8);
    } elseif (str_starts_with($argument, '--public-dir=')) {
        $publicDir = substr($argument, 13);
    } elseif (str_starts_with($argument, '--compat-dir=')) {
        $compatDir = substr($argument, 13);
    } else {
        fwrite(STDERR, "Unknown option: {$argument}\n");
        exit(2);
    }
}

$assetPrefix = 'themes/DefaultTheme/src/resources/assets/';
$viewPrefix = 'themes/DefaultTheme/src/resources/views/';
$allowedExtensions = ['css', 'js', 'json', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'map'];
$manifestPath = $stage.'/manifest.json';
$workingDirs = [];

try {
    if (! is_file($manifestPath)) {
        throw new RuntimeException('The staged manifest.json is missing. Run stage-laravel-shop.php first.');
    }

    $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
    if (! isset($manifest['files']) || ! is_array($manifest['files'])) {
        throw new RuntimeException('Invalid staging manifest.');
    }

    $stageReal = realpath($stage);
    $assets = [];
    $views = [];

    foreach ($manifest['files'] as $file) {
        $path = $file['path'] ?? '';
        $asset = str_starts_with($path, $assetPrefix);
        $view = in_array($path, [
            $viewPrefix.'layouts/master.blade.php',
            $viewPrefix.'index.blade.php',
        ], true);

        if (! $asset && ! $view) {
            continue;
        }

        if (str_contains($path, '\\') || str_contains($path, '..') || preg_match('/[\x00-\x1F\x7F]/', $path)) {
            throw new RuntimeException('Unsafe manifest path: '.$path);
        }

        if ($asset && ! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $allowedExtensions, true)) {
            continue;
        }

        $absolute = realpath($stage.'/'.$path);
        if ($absolute === false || $stageReal === false
            || ! str_starts_with($absolute, $stageReal.DIRECTORY_SEPARATOR)
            || ! is_file($absolute) || is_link($stage.'/'.$path)
        ) {
            throw new RuntimeException('Missing or unsafe staged file: '.$path);
        }

        if (filesize($absolute) !== ($file['size'] ?? null)
            || ! hash_equals((string) ($file['sha256'] ?? ''), hash_file('sha256', $absolute))
        ) {
            throw new RuntimeException('Checksum mismatch for '.$path);
        }

        if ($asset) {
            $relative = substr($path, strlen($assetPrefix));
            if ($relative === '' || str_contains($relative, '/.') || str_starts_with($relative, '.')) {
                continue;
            }
            $assets[] = ['absolute' => $absolute, 'relative' => $relative, 'sha256' => $file['sha256']];
        } else {
            $views[substr($path, strlen($viewPrefix))] = file_get_contents($absolute);
        }
    }

    if (! isset($views['layouts/master.blade.php'], $views['index.blade.php'])
        || $assets === []
    ) {
        throw new RuntimeException('The original DefaultTheme index, master layout, or assets are missing.');
    }

    printf("Plan: %d original static assets to %s\n", count($assets), $publicDir);
    echo "Original views remain private and receive only route/build compatibility changes.\n";

    if (! $apply) {
        echo "Dry run: no files written. Add --apply to publish locally.\n";
        exit(0);
    }

    if (file_exists($publicDir) || file_exists($compatDir) || is_link($publicDir) || is_link($compatDir)) {
        throw new RuntimeException('Output already exists; refusing to overwrite.');
    }

    foreach ([dirname($publicDir), dirname($compatDir)] as $parent) {
        if (! is_dir($parent) && ! mkdir($parent, 0700, true) && ! is_dir($parent)) {
            throw new RuntimeException('Cannot create the destination parent directory.');
        }
    }

    $temporaryPublic = $publicDir.'.incomplete-'.bin2hex(random_bytes(4));
    $temporaryCompat = $compatDir.'.incomplete-'.bin2hex(random_bytes(4));
    $workingDirs = [$temporaryPublic, $temporaryCompat];

    if (! mkdir($temporaryPublic, 0700) || ! mkdir($temporaryCompat, 0700)) {
        throw new RuntimeException('Cannot create temporary publication directories.');
    }

    $published = [];
    foreach ($assets as $asset) {
        $destination = $temporaryPublic.'/'.$asset['relative'];
        $dir = dirname($destination);
        if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new RuntimeException('Cannot create asset directory.');
        }
        if (! copy($asset['absolute'], $destination)) {
            throw new RuntimeException('Cannot copy asset '.$asset['relative']);
        }
        $published[] = ['path' => $asset['relative'], 'sha256' => $asset['sha256']];
    }

    $master = $views['layouts/master.blade.php'];
    $index = $views['index.blade.php'];

    // The old Mix manifest must never be used; local assets are served directly.
    if (substr_count($master, "@if (config('app.debug'))") < 1) {
        throw new RuntimeException('Unexpected original theme layout. Review before adapting.');
    }
    $master = str_replace("@if (config('app.debug'))", '@if (true)', $master);

    foreach ([
        "route('front.index')" => "route('storefront.preview.home')",
        "route('front.products.search')" => "route('storefront.preview.products')",
    ] as $old => $new) {
        $master = str_replace($old, $new, $master);
        $index = str_replace($old, $new, $index);
    }

    mkdir($temporaryCompat.'/layouts', 0700, true);
    file_put_contents($temporaryCompat.'/layouts/master.blade.php', $master);
    file_put_contents($temporaryCompat.'/index.blade.php', $index);
    file_put_contents(
        $temporaryPublic.'/asset-manifest.json',
        json_encode(['files' => $published], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
    );

    if (! rename($temporaryPublic, $publicDir)) {
        throw new RuntimeException('Cannot finalize published assets.');
    }
    $workingDirs = [$temporaryCompat];

    if (! rename($temporaryCompat, $compatDir)) {
        throw new RuntimeException('Cannot finalize private adapted views; remove incomplete published assets manually.');
    }
    $workingDirs = [];

    echo 'Published '.count($assets)." original assets. Compatibility views are private.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Theme publication aborted: '.$exception->getMessage()."\n");
    foreach ($workingDirs as $folder) {
        if (! is_dir($folder)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($folder);
    }
    exit(1);
}
