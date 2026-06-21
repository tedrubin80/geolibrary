<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\Http;

use GEOOptimizer\Http\UrlValidator;
use PHPUnit\Framework\TestCase;

class UrlValidatorTest extends TestCase
{
    public function testRejectsPrivateWebhookTargets(): void
    {
        $this->assertFalse(UrlValidator::isAllowedWebhookUrl('http://example.com/hook'));
        $this->assertFalse(UrlValidator::isAllowedWebhookUrl('https://localhost/hook'));
        $this->assertFalse(UrlValidator::isAllowedWebhookUrl('https://127.0.0.1/hook'));
        $this->assertFalse(UrlValidator::isAllowedWebhookUrl('not-a-url'));
    }

    public function testAllowsPublicHttpsWebhook(): void
    {
        $this->assertTrue(UrlValidator::isAllowedWebhookUrl('https://example.com/webhook'));
    }
}
