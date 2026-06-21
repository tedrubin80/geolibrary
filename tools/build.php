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
            if (str_contains($filePath, '/vendor/')
                || str_contains($filePath, '/dist/')
                || str_contains($filePath, '/coverage/')
                || str_contains($filePath, '/.git/')
            ) {
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

if (is_dir($pluginBuild)) {
    passthru('rm -rf ' . escapeshellarg($pluginBuild));
}
mkdir($pluginBuild, 0755, true);

passthru('cp -R ' . escapeshellarg($pluginDir . '/.') . ' ' . escapeshellarg($pluginBuild), $copyStatus);
if ($copyStatus !== 0) {
    fwrite(STDERR, "Failed to copy plugin files\n");
    exit(1);
}

passthru(
    'composer install --no-dev --prefer-dist --no-progress --working-dir=' . escapeshellarg($pluginBuild),
    $composerStatus
);
if ($composerStatus !== 0) {
    fwrite(STDERR, "Plugin composer install failed\n");
    exit(1);
}

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

    $local = 'geooptimizer/' . substr($filePath, strlen($pluginBuild) + 1);
    $zip->addFile($filePath, $local);
}

$zip->close();
echo "Created {$pluginZip}\n";
echo "Done.\n";
