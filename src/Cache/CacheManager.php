<?php

declare(strict_types=1);

namespace GEOOptimizer\Cache;

use GEOOptimizer\Exceptions\CacheException;

/**
 * Cache Manager
 *
 * Factory and facade for cache operations
 */
class CacheManager
{
    private static ?CacheInterface $instance = null;

    /**
     * @var array<string, mixed>
     */
    private static array $config = [];

    /**
     * Get cache instance
     *
     * @param array<string, mixed> $config
     * @throws CacheException
     */
    public static function getInstance(array $config = []): CacheInterface
    {
        if (self::$instance === null) {
            self::$config = array_merge(self::getDefaultConfig(), $config);
            self::$instance = self::createAdapter(self::$config);
        }

        return self::$instance;
    }

    /**
     * Create cache adapter based on configuration
     *
     * @param array<string, mixed> $config
     * @throws CacheException
     */
    private static function createAdapter(array $config): CacheInterface
    {
        $adapter = $config['adapter'] ?? 'file';

        switch ($adapter) {
            case 'file':
                return new FileCache($config);

            case 'redis':
                return new RedisCache($config);

            case 'memory':
                return new MemoryCache($config);

            case 'null':
                return new NullCache();

            default:
                throw new CacheException("Unknown cache adapter: {$adapter}");
        }
    }

    /**
     * Reset cache instance (useful for testing)
     */
    public static function reset(): void
    {
        self::$instance = null;
        self::$config = [];
    }

    /**
     * Get default configuration
     *
     * @return array<string, mixed>
     */
    private static function getDefaultConfig(): array
    {
        return [
            'adapter' => 'file',
            'path' => sys_get_temp_dir() . '/geo_cache',
            'ttl' => 3600, // 1 hour default
            'prefix' => 'geo_',
            'serialize' => true
        ];
    }

    /**
     * Create a cache key from parameters
     *
     * @param array<string, mixed> $params
     */
    public static function createKey(string $prefix, array $params = []): string
    {
        $key = $prefix;

        if (!empty($params)) {
            ksort($params);
            $key .= '_' . md5(serialize($params));
        }

        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) ?? $key;
    }

    /**
     * Cache a callable's result
     *
     * @throws CacheException
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cache = self::getInstance();

        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $value = $callback();
        $cache->set($key, $value, $ttl);

        return $value;
    }
}