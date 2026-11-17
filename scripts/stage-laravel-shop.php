<?php

/**
 * Prepare a private, non-executable staging area for Laravel Shop 1.19.8.
 *
 * The archived PHP, CSS and Blade files are copied for review only. They are
 * never loaded, registered as routes, or published by this command.
 */
declare(strict_types=1);

use ZipArchive as Zip;

if (PHP_SAPI !== 'cli' || ! class_exists(Zip::class)) {
    fwrite(STDERR, "This command requires PHP CLI with the zip extension.\n");
    exit(2);
}

$source = $argv[1] ?? '';
$apply = in_array('--apply', $argv, true);
$targetOption = null;

foreach (array_slice($argv, 2) as $option) {
    if (str_starts_with($option, '--target=')) {
        $targetOption = substr($option, strlen('--target='));
    } elseif ($option !== '--apply') {
        fwrite(STDERR, "Unknown option: {$option}\n");
        exit(2);
    }
}

if ($source === '' || ! is_file($source)) {
    fwrite(STDERR, "Usage: php scripts/stage-laravel-shop.php <archive.zip> [--apply] [--target=/private/path]\n");
    exit(2);
}

$projectRoot = dirname(__DIR__);
$target = $targetOption ?? $projectRoot.'/storage/app/private/legacy-shop-1.19.8';
$nestedArchivePath = null;
$archive = new Zip;
$entries = [];

try {
    if ($archive->open($source) !== true) {
        throw new RuntimeException('Cannot read the supplied ZIP.');
    }

    $nestedName = 'main-1.19.8/script/webapp-v1.19.8.zip';
    if ($archive->locateName($nestedName) !== false) {
        $nestedArchivePath = tempnam(sys_get_temp_dir(), 'vira-shop-');
        if ($nestedArchivePath === false) {
            throw new RuntimeException('Cannot create a temporary file.');
        }

        $input = $archive->getStream($nestedName);
        $output = fopen($nestedArchivePath, 'wb');
        if ($input === false || $output === false) {
            throw new RuntimeException('Cannot read the nested project archive.');
        }

        stream_copy_to_stream($input, $output);
        fclose($input);
        fclose($output);
        $archive->close();

        if ($archive->open($nestedArchivePath) !== true) {
            throw new RuntimeException('Cannot open the nested project archive.');
        }
    }

    $prefixes = ['themes/DefaultTheme/', 'resources/views/back/', 'public/back/'];
    $totalSize = 0;
    $caseInsensitivePaths = [];

    for ($i = 0; $i < $archive->numFiles; $i++) {
        $name = $archive->getNameIndex($i);
        if ($name === false || str_ends_with($name, '/')) {
            continue;
        }

        $selected = false;
        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                $selected = true;
                break;
            }
        }
        if (! $selected) {
            continue;
        }

        $segments = explode('/', $name);
        if (str_contains($name, '\\') || str_contains($name, ':')
            || preg_match('/[\x00-\x1F\x7F]/', $name)
            || in_array('..', $segments, true)
            || in_array('', $segments, true)
        ) {
            throw new RuntimeException('Unsafe ZIP path rejected: '.$name);
        }

        $hidden = false;
        foreach ($segments as $segment) {
            if (str_starts_with($segment, '.')) {
                $hidden = true;
                break;
            }
        }
        if ($hidden) {
            continue;
        }

        $lowerName = strtolower($name);
        if (isset($caseInsensitivePaths[$lowerName])) {
            throw new RuntimeException('Duplicate case-insensitive ZIP path: '.$name);
        }
        $caseInsensitivePaths[$lowerName] = true;

        $zipOpsys = 0;
        $attributes = 0;
        if ($archive->getExternalAttributesIndex($i, $zipOpsys, $attributes) && (($attributes >> 16) & 0170000) === 0120000) {
            throw new RuntimeException('Symbolic links are not allowed: '.$name);
        }

        $stat = $archive->statIndex($i);
        $size = $stat['size'] ?? -1;
        if ($size < 0 || $size > 30 * 1024 * 1024) {
            throw new RuntimeException('File exceeds per-file size limit: '.$name);
        }
        $totalSize += $size;
        if ($totalSize > 200 * 1024 * 1024 || count($entries) >= 5000) {
            throw new RuntimeException('Archive exceeds staging limits.');
        }

        $entries[] = ['index' => $i, 'path' => $name, 'size' => $size];
    }

    if ($entries === []) {
        throw new RuntimeException('No Laravel Shop theme or admin files were found.');
    }

    printf("Plan: %d files, %d bytes, from %s\n", count($entries), $totalSize, $source);

    if (! $apply) {
        echo "Dry run only. Add --apply to copy files into an isolated, private directory.\n";
        $archive->close();
        if ($nestedArchivePath !== null && is_file($nestedArchivePath)) {
            unlink($nestedArchivePath);
            $nestedArchivePath = null;
        }
        exit(0);
    }

    if (file_exists($target) || is_link($target)) {
        throw new RuntimeException('Target already exists; refusing to overwrite: '.$target);
    }

    $parent = dirname($target);
    if (! is_dir($parent) && ! mkdir($parent, 0700, true) && ! is_dir($parent)) {
        throw new RuntimeException('Cannot create the private staging parent directory.');
    }

    // Prevent accidental publication under the application's public web root.
    $resolvedParent = realpath($parent);
    $publicRoot = realpath($projectRoot.'/public');
    if ($resolvedParent === false || ($publicRoot !== false
        && ($resolvedParent === $publicRoot || str_starts_with($resolvedParent.DIRECTORY_SEPARATOR, $publicRoot.DIRECTORY_SEPARATOR)))
    ) {
        throw new RuntimeException('Staging under the public web root is forbidden.');
    }

    $temporaryTarget = $target.'.incomplete-'.bin2hex(random_bytes(5));
    if (! mkdir($temporaryTarget, 0700)) {
        throw new RuntimeException('Cannot create the temporary staging directory.');
    }

    try {
        $manifest = ['source' => basename($source), 'files' => []];

        foreach ($entries as $entry) {
            $destination = $temporaryTarget.'/'.$entry['path'];
            $directory = dirname($destination);
            if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                throw new RuntimeException('Cannot create staging directory for '.$entry['path']);
            }

            $input = $archive->getStream($entry['path']);
            $output = fopen($destination, 'xb');
            if ($input === false || $output === false) {
                throw new RuntimeException('Cannot read or write '.$entry['path']);
            }

            $copied = stream_copy_to_stream($input, $output);
            fclose($input);
            fclose($output);

            if ($copied !== $entry['size']) {
                throw new RuntimeException('File size verification failed for '.$entry['path']);
            }

            chmod($destination, 0600);
            $manifest['files'][] = [
                'path' => $entry['path'],
                'size' => $entry['size'],
                'sha256' => hash_file('sha256', $destination),
            ];
        }

        file_put_contents(
            $temporaryTarget.'/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        if (! rename($temporaryTarget, $target)) {
            throw new RuntimeException('Cannot finalize the private staging directory.');
        }
    } catch (Throwable $exception) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($temporaryTarget, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($temporaryTarget);
        throw $exception;
    }

    echo 'Staged '.count($entries).' files privately at: '.$target."\n";
    echo "Files are not served publicly or registered with Laravel.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Import staging aborted: '.$exception->getMessage()."\n");
    exit(1);
} finally {
    $archive->close();
    if ($nestedArchivePath !== null && is_file($nestedArchivePath)) {
        unlink($nestedArchivePath);
    }
}
