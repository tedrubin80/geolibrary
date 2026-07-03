<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\API;

use GEOOptimizer\API\RateLimiter;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/geo_rate_limit_test_' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->storagePath)) {
            array_map('unlink', glob($this->storagePath . '/*') ?: []);
            rmdir($this->storagePath);
        }
    }

    public function testAllowRespectsMaxRequests(): void
    {
        $limiter = new RateLimiter(2, 60, $this->storagePath);

        $this->assertTrue($limiter->allow('client-a'));
        $this->assertTrue($limiter->allow('client-a'));
        $this->assertFalse($limiter->allow('client-a'));
    }

    public function testClientsAreIsolated(): void
    {
        $limiter = new RateLimiter(1, 60, $this->storagePath);

        $this->assertTrue($limiter->allow('client-a'));
        $this->assertFalse($limiter->allow('client-a'));
        $this->assertTrue($limiter->allow('client-b'));
    }
}
