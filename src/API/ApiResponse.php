<?php

declare(strict_types=1);

namespace GEOOptimizer\API;

/**
 * JSON API response helper.
 */
final class ApiResponse
{
    /**
     * @param array<string, mixed> $payload
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function json(array $payload, int $status = 200): array
    {
        return [
            'status' => $status,
            'headers' => [
                'Content-Type' => 'application/json; charset=UTF-8',
            ],
            'body' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}',
        ];
    }

    /**
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function error(string $message, int $status = 400, ?string $code = null): array
    {
        $payload = [
            'success' => false,
            'error' => [
                'message' => $message,
            ],
        ];

        if ($code !== null) {
            $payload['error']['code'] = $code;
        }

        return self::json($payload, $status);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function success(array $data, int $status = 200): array
    {
        return self::json([
            'success' => true,
            'data' => $data,
        ], $status);
    }
}
