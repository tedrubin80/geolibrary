<?php

declare(strict_types=1);

namespace GEOOptimizer\API;

use GEOOptimizer\GEOOptimizer;
use GEOOptimizer\Exceptions\GEOException;
use GEOOptimizer\Exceptions\ValidationException;

/**
 * Framework-agnostic REST API controller for GEO automation.
 */
class RESTController
{
    private GEOOptimizer $geoOptimizer;
    private RateLimiter $rateLimiter;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(?GEOOptimizer $geoOptimizer = null, ?RateLimiter $rateLimiter = null, array $config = [])
    {
        $this->config = array_merge([
            'api_key' => getenv('GEO_API_KEY') ?: '',
            'rate_limit' => 60,
            'rate_window' => 60,
        ], $config);

        $this->geoOptimizer = $geoOptimizer ?? new GEOOptimizer();
        $this->rateLimiter = $rateLimiter ?? new RateLimiter(
            (int) $this->config['rate_limit'],
            (int) $this->config['rate_window']
        );
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $query
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public function handle(string $method, string $path, array $headers = [], $body = null, array $query = []): array
    {
        $method = strtoupper($method);
        $path = '/' . trim(parse_url($path, PHP_URL_PATH) ?: $path, '/');
        $clientId = $this->resolveClientId($headers);

        if (!$this->rateLimiter->allow($clientId)) {
            $response = ApiResponse::error('Rate limit exceeded', 429, 'rate_limit_exceeded');
            $response['headers']['Retry-After'] = (string) $this->rateLimiter->retryAfterSeconds($clientId);

            return $response;
        }

        if (!$this->isAuthorized($headers, $path)) {
            return ApiResponse::error('Unauthorized', 401, 'unauthorized');
        }

        try {
            return $this->route($method, $path, $body, $query);
        } catch (ValidationException $exception) {
            return ApiResponse::error($exception->getMessage(), 422, 'validation_error');
        } catch (GEOException $exception) {
            return ApiResponse::error($exception->getMessage(), 500, 'geo_error');
        }
    }

    /**
     * @return array<int, array{method: string, path: string, description: string}>
     */
    public function routes(): array
    {
        return [
            ['method' => 'GET', 'path' => '/health', 'description' => 'Health check'],
            ['method' => 'GET', 'path' => '/v1/industries', 'description' => 'List supported industries'],
            ['method' => 'POST', 'path' => '/v1/optimize', 'description' => 'Run full GEO optimization'],
            ['method' => 'POST', 'path' => '/v1/analyze', 'description' => 'Analyze content for GEO readiness'],
            ['method' => 'POST', 'path' => '/v1/llms-txt', 'description' => 'Generate llms.txt content'],
            ['method' => 'POST', 'path' => '/v1/schema', 'description' => 'Generate Schema.org JSON-LD'],
        ];
    }

    /**
     * @param array<string, string> $headers
     */
    private function isAuthorized(array $headers, string $path): bool
    {
        if ($path === '/health') {
            return true;
        }

        $configuredKey = (string) ($this->config['api_key'] ?? '');
        if ($configuredKey === '') {
            return true;
        }

        $provided = $headers['x-api-key'] ?? $headers['X-Api-Key'] ?? $headers['X-API-KEY'] ?? '';

        return hash_equals($configuredKey, (string) $provided);
    }

    /**
     * @param array<string, string> $headers
     */
    private function resolveClientId(array $headers): string
    {
        return $headers['x-api-key']
            ?? $headers['X-Api-Key']
            ?? $headers['x-forwarded-for']
            ?? $headers['X-Forwarded-For']
            ?? 'anonymous';
    }

    /**
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function route(string $method, string $path, $body, array $query): array
    {
        if ($method === 'GET' && $path === '/health') {
            return ApiResponse::success([
                'status' => 'ok',
                'version' => GEOOptimizer::VERSION,
                'routes' => $this->routes(),
            ]);
        }

        if ($method === 'GET' && $path === '/v1/industries') {
            return ApiResponse::success([
                'industries' => $this->geoOptimizer->getAvailableIndustries(),
            ]);
        }

        $payload = $this->decodeBody($body);

        if ($method === 'POST' && $path === '/v1/optimize') {
            return ApiResponse::success($this->geoOptimizer->optimize($payload));
        }

        if ($method === 'POST' && $path === '/v1/analyze') {
            $content = (string) ($payload['content'] ?? '');
            $options = is_array($payload['options'] ?? null) ? $payload['options'] : [];

            return ApiResponse::success($this->geoOptimizer->analyzeContent($content, $options));
        }

        if ($method === 'POST' && $path === '/v1/llms-txt') {
            return ApiResponse::success([
                'content' => $this->geoOptimizer->generateLLMSTxt($payload),
            ]);
        }

        if ($method === 'POST' && $path === '/v1/schema') {
            $type = (string) ($payload['type'] ?? 'LocalBusiness');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

            return ApiResponse::success([
                'schema' => $this->geoOptimizer->generateSchema($type, $data),
                'json_ld' => $this->geoOptimizer->generateStructuredData(array_merge($data, [
                    'schema_type' => $type,
                    'business_type' => $type,
                ])),
            ]);
        }

        return ApiResponse::error('Route not found', 404, 'not_found');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBody($body): array
    {
        if (is_array($body)) {
            return $body;
        }

        if (!is_string($body) || trim($body) === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
