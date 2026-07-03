<?php

declare(strict_types=1);

namespace GEOOptimizer\Integrations\WordPress\Premium;

/**
 * Validates GeoOptimizer premium license keys.
 */
final class PremiumLicense
{
    public const DEMO_KEY = 'GEO-DEMO-9444';

    public static function isValid(string $licenseKey): bool
    {
        $licenseKey = strtoupper(trim($licenseKey));

        if ($licenseKey === self::DEMO_KEY) {
            return true;
        }

        if (!preg_match('/^GEO-([A-Z0-9]{4})-(\d{4})$/', $licenseKey, $matches)) {
            return false;
        }

        $expected = self::checksum($matches[1]);

        return (int) $matches[2] === $expected;
    }

    public static function checksum(string $segment): int
    {
        return abs(crc32($segment)) % 10000;
    }
}
