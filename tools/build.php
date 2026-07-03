<?php

declare(strict_types=1);

/**
 * Build distributable artifacts for GeoOptimizer.
 *
 * Usage: php tools/build.php [--plugin]
 */

$root = dirname(__DIR__);
$dist = $root . '/dist';

if (!is_dir($dist)) {
    mkdir($dist, 0755, true);
}

$buildPlugin = in_array('--plugin', $argv, true);

/**
 * @param list<string> $excludePathFragments
 */
function shouldExcludePath(string $filePath, array $excludePathFragments): bool
{
    $normalized = str_replace('\\', '/', $filePath);

    foreach ($excludePathFragments as $fragment) {
        if ($fragment === '/dist/') {
            if (preg_match('#/dist/((?!plugin-build|library-staging).)+#', $normalized) === 1) {
                return true;
            }
            continue;
        }

        if (str_contains($normalized, $fragment)) {
            return true;
        }
    }

    return false;
}

/**
 * @param list<string> $paths
 */
function copyDirectory(string $source, string $destination, array $paths = []): void
{
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    if ($paths === []) {
        passthru(
            'cp -R ' . escapeshellarg($source . '/.') . ' ' . escapeshellarg($destination),
            $status
        );
        if ($status !== 0) {
            throw new RuntimeException("Failed to copy {$source} to {$destination}");
        }

        return;
    }

    foreach ($paths as $path) {
        $from = $source . '/' . $path;
        $to = $destination . '/' . $path;

        if (is_dir($from)) {
            if (!is_dir($to)) {
                mkdir($to, 0755, true);
            }
            passthru(
                'cp -R ' . escapeshellarg($from . '/.') . ' ' . escapeshellarg($to),
                $status
            );
            if ($status !== 0) {
                throw new RuntimeException("Failed to copy {$from}");
            }
            continue;
        }

        if (!file_exists($from)) {
            continue;
        }

        $parent = dirname($to);
        if (!is_dir($parent)) {
            mkdir($parent, 0755, true);
        }

        if (!copy($from, $to)) {
            throw new RuntimeException("Failed to copy {$from}");
        }
    }
}

echo "Building GeoOptimizer library artifact...\n";

$libraryZip = $dist . '/geooptimizer-php-library.zip';
$zip = new ZipArchive();

if ($zip->open($libraryZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Unable to create {$libraryZip}\n");
    exit(1);
}

$libraryPaths = [
    'src',
    'bin',
    'composer.json',
    'README.md',
    'LICENSE',
];

$libraryExcludeFragments = [
    '/vendor/',
    '/dist/',
    '/coverage/',
    '/.git/',
    '/tests/',
    '/wordpress-plugin/',
];

foreach ($libraryPaths as $path) {
    $full = $root . '/' . $path;
    if (!file_exists($full)) {
        continue;
    }

    if (is_dir($full)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getPathname();
            if (shouldExcludePath($filePath, $libraryExcludeFragments)) {
                continue;
            }

            $local = $path . '/' . substr($filePath, strlen($full) + 1);
            $zip->addFile($filePath, $local);
        }
    } else {
        $zip->addFile($full, $path);
    }
}

$zip->close();
echo "Created {$libraryZip}\n";

if (!$buildPlugin) {
    echo "Done. Pass --plugin to also build the WordPress plugin zip.\n";
    exit(0);
}

echo "Building WordPress plugin artifact...\n";

$pluginDir = $root . '/wordpress-plugin/geooptimizer';
$pluginBuild = $dist . '/plugin-build';
$libraryStaging = $dist . '/library-staging';

foreach ([$pluginBuild, $libraryStaging] as $directory) {
    if (is_dir($directory)) {
        passthru('rm -rf ' . escapeshellarg($directory));
    }
}

try {
    copyDirectory($pluginDir, $pluginBuild);
    copyDirectory($root, $libraryStaging, ['src', 'bin', 'composer.json', 'LICENSE', 'README.md']);
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

$pluginComposerPath = $pluginBuild . '/composer.json';
$pluginComposer = json_decode((string) file_get_contents($pluginComposerPath), true);
if (!is_array($pluginComposer)) {
    fwrite(STDERR, "Invalid plugin composer.json\n");
    exit(1);
}

$pluginComposer['repositories'][0]['url'] = '../library-staging';
file_put_contents(
    $pluginComposerPath,
    json_encode($pluginComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
);

passthru(
    'composer install --no-dev --prefer-dist --no-progress --working-dir=' . escapeshellarg($pluginBuild),
    $composerStatus
);
if ($composerStatus !== 0) {
    fwrite(STDERR, "Plugin composer install failed\n");
    exit(1);
}

$pluginExcludeFragments = [
    '/.git/',
    '/tests/',
    '/coverage/',
    '/dist/',
    '/docs/',
    '/public/',
    '/wordpress-plugin/',
    '/tools/',
    '/.github/',
    '/.phpunit.result.cache',
    '/phpunit.xml',
    '/phpstan.neon.dist',
];

$pluginZip = $dist . '/geooptimizer-wordpress-plugin.zip';
$zip = new ZipArchive();
if ($zip->open($pluginZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Unable to create {$pluginZip}\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pluginBuild, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if ($file->isDir()) {
        continue;
    }

    $filePath = $file->getRealPath();
    if ($filePath === false) {
        continue;
    }

    if (shouldExcludePath($filePath, $pluginExcludeFragments)) {
        continue;
    }

    $local = 'geooptimizer/' . substr($filePath, strlen($pluginBuild) + 1);
    $zip->addFile($filePath, $local);
}

$zip->close();

if (!file_exists($pluginZip) || filesize($pluginZip) === 0) {
    fwrite(STDERR, "Plugin zip was not created or is empty\n");
    exit(1);
}

echo "Created {$pluginZip}\n";
echo "Done.\n";
