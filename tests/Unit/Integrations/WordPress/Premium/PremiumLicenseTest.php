<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\Integrations\WordPress\Premium;

use GEOOptimizer\Integrations\WordPress\Premium\PremiumLicense;
use PHPUnit\Framework\TestCase;

class PremiumLicenseTest extends TestCase
{
    public function testDemoKeyIsValid(): void
    {
        $this->assertTrue(PremiumLicense::isValid(PremiumLicense::DEMO_KEY));
    }

    public function testGeneratedKeyFormatIsValid(): void
    {
        $segment = 'TEST';
        $key = sprintf('GEO-%s-%04d', $segment, PremiumLicense::checksum($segment));

        $this->assertTrue(PremiumLicense::isValid($key));
    }

    public function testInvalidKeyIsRejected(): void
    {
        $this->assertFalse(PremiumLicense::isValid('INVALID'));
    }
}
