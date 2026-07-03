<?php

declare(strict_types=1);

/**
 * Run release checks before tagging or submitting to Packagist / WordPress.org.
 *
 * Usage: php tools/publish-verify.php
 */

$root = dirname(__DIR__);
chdir($root);

$steps = [
    ['composer test', 'PHPUnit'],
    ['composer phpstan', 'PHPStan'],
    ['composer audit', 'Composer audit'],
    ['php tools/build.php --plugin', 'Build artifacts'],
];

foreach ($steps as [$command, $label]) {
    echo "==> {$label}\n";
    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, "Failed: {$label}\n");
        exit(1);
    }
    echo "\n";
}

$artifacts = [
    'dist/geooptimizer-php-library.zip',
    'dist/geooptimizer-wordpress-plugin.zip',
];

foreach ($artifacts as $path) {
    if (!is_file($path) || filesize($path) === 0) {
        fwrite(STDERR, "Missing or empty artifact: {$path}\n");
        exit(1);
    }
}

require $root . '/vendor/autoload.php';

echo 'Version: ' . GEOOptimizer\Version::VERSION . PHP_EOL;
echo "Artifacts OK:\n";
foreach ($artifacts as $path) {
    printf("  %s (%s bytes)\n", $path, number_format((int) filesize($path)));
}

echo "\nReady to tag and push, or submit to Packagist / WordPress.org.\n";
