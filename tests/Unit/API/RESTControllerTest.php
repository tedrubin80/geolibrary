<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\API;

use GEOOptimizer\API\RESTController;
use PHPUnit\Framework\TestCase;

class RESTControllerTest extends TestCase
{
    private RESTController $controller;

    protected function setUp(): void
    {
        $this->controller = new RESTController(null, null, [
            'api_key' => 'secret-key',
            'rate_limit' => 100,
            'rate_window' => 60,
        ]);
    }

    public function testHealthEndpointIsPublic(): void
    {
        $response = $this->controller->handle('GET', '/health');

        $this->assertSame(200, $response['status']);
        $payload = json_decode($response['body'], true);
        $this->assertTrue($payload['success']);
        $this->assertSame('ok', $payload['data']['status']);
    }

    public function testProtectedRouteRequiresApiKey(): void
    {
        $response = $this->controller->handle('GET', '/v1/industries');

        $this->assertSame(401, $response['status']);
    }

    public function testIndustriesEndpointReturnsList(): void
    {
        $response = $this->controller->handle('GET', '/v1/industries', [
            'x-api-key' => 'secret-key',
        ]);

        $this->assertSame(200, $response['status']);
        $payload = json_decode($response['body'], true);
        $this->assertContains('restaurant', $payload['data']['industries']);
    }

    public function testAnalyzeEndpointValidatesContent(): void
    {
        $response = $this->controller->handle(
            'POST',
            '/v1/analyze',
            ['x-api-key' => 'secret-key'],
            json_encode(['content' => ''])
        );

        $this->assertSame(422, $response['status']);
    }

    public function testLlmsTxtEndpointGeneratesContent(): void
    {
        $response = $this->controller->handle(
            'POST',
            '/v1/llms-txt',
            ['x-api-key' => 'secret-key'],
            json_encode([
                'business_name' => 'Acme Coffee',
                'name' => 'Acme Coffee',
                'description' => 'Specialty coffee shop.',
                'industry' => 'restaurant',
            ])
        );

        $this->assertSame(200, $response['status']);
        $payload = json_decode($response['body'], true);
        $this->assertStringContainsString('Acme Coffee', $payload['data']['content']);
    }

    public function testBulkAnalyzeEndpoint(): void
    {
        $response = $this->controller->handle(
            'POST',
            '/v1/bulk-analyze',
            ['x-api-key' => 'secret-key'],
            json_encode([
                'items' => [
                    ['id' => 'a', 'content' => 'Certified professional coffee roaster in San Francisco with award-winning espresso.'],
                    ['id' => 'b', 'content' => 'Coffee.'],
                ],
            ])
        );

        $this->assertSame(200, $response['status']);
        $payload = json_decode($response['body'], true);
        $this->assertSame(2, $payload['data']['count']);
    }

    public function testCompareEndpoint(): void
    {
        $response = $this->controller->handle(
            'POST',
            '/v1/compare',
            ['x-api-key' => 'secret-key'],
            json_encode([
                'primary_name' => 'Acme',
                'primary_content' => 'Certified professional coffee roaster in San Francisco with award-winning espresso.',
                'competitors' => [
                    ['name' => 'Rival', 'content' => 'Coffee shop.'],
                ],
            ])
        );

        $this->assertSame(200, $response['status']);
        $payload = json_decode($response['body'], true);
        $this->assertSame('Acme', $payload['data']['primary']['name']);
    }
}
