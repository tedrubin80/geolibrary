<?php

declare(strict_types=1);

namespace GEOOptimizer\Cache;

use GEOOptimizer\Exceptions\CacheException;

/**
 * JSON-based cache serialization (avoids PHP unserialize object injection).
 */
final class CacheSerializer
{
    /**
     * @throws CacheException
     */
    public static function encode(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CacheException('Cache value is not JSON-serializable: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws CacheException
     */
    public static function decode(string $payload): mixed
    {
        try {
            return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CacheException('Invalid cache payload: ' . $e->getMessage(), 0, $e);
        }
    }
}
