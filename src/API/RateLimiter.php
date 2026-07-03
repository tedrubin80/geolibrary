<?php

declare(strict_types=1);

namespace GEOOptimizer\API;

/**
 * Simple file-backed API rate limiter.
 */
class RateLimiter
{
    private string $storagePath;
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(
        int $maxRequests = 60,
        int $windowSeconds = 60,
        ?string $storagePath = null
    ) {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->storagePath = $storagePath ?? sys_get_temp_dir() . '/geo_api_rate_limit';
    }

    public function allow(string $clientId): bool
    {
        $this->ensureStoragePath();

        $file = $this->fileForClient($clientId);
        $now = time();
        $requests = [];

        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded)) {
                $requests = $decoded;
            }
        }

        $windowSeconds = $this->windowSeconds;
        $requests = array_values(array_filter(
            $requests,
            static fn ($timestamp) => is_int($timestamp) && ($now - $timestamp) < $windowSeconds
        ));

        if (count($requests) >= $this->maxRequests) {
            return false;
        }

        $requests[] = $now;
        file_put_contents($file, json_encode($requests));

        return true;
    }

    public function retryAfterSeconds(string $clientId): int
    {
        $file = $this->fileForClient($clientId);
        if (!is_file($file)) {
            return 0;
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        if (!is_array($decoded) || $decoded === []) {
            return 0;
        }

        $oldest = min(array_filter($decoded, 'is_int'));
        $remaining = $this->windowSeconds - (time() - $oldest);

        return max(0, $remaining);
    }

    private function fileForClient(string $clientId): string
    {
        return $this->storagePath . '/' . hash('sha256', $clientId) . '.json';
    }

    private function ensureStoragePath(): void
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }
}
