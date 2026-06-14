<?php

declare(strict_types=1);

namespace GEOOptimizer\Cache;

use GEOOptimizer\Exceptions\CacheException;

/**
 * Redis Cache Implementation
 *
 * High-performance Redis-based caching for production environments
 */
class RedisCache implements CacheInterface
{
    private ?\Redis $redis = null;
    private string $prefix;
    private int $defaultTtl;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'database' => 0,
            'timeout' => 2.0,
            'prefix' => 'geo_',
            'ttl' => 3600
        ], $config);

        $this->prefix = $this->config['prefix'];
        $this->defaultTtl = $this->config['ttl'];
    }

    /**
     * Get Redis connection
     *
     * @throws CacheException
     */
    private function getRedis(): \Redis
    {
        if ($this->redis === null) {
            if (!extension_loaded('redis')) {
                throw new CacheException('Redis extension is not installed');
            }

            $this->redis = new \Redis();

            try {
                $connected = $this->redis->connect(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['timeout']
                );

                if (!$connected) {
                    throw new CacheException('Failed to connect to Redis');
                }

                if ($this->config['password'] !== null) {
                    $this->redis->auth($this->config['password']);
                }

                if ($this->config['database'] !== 0) {
                    $this->redis->select($this->config['database']);
                }
            } catch (\RedisException $e) {
                throw new CacheException('Redis connection failed: ' . $e->getMessage(), 0, $e);
            }
        }

        return $this->redis;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $redis = $this->getRedis();
            $value = $redis->get($this->prefix . $key);

            if ($value === false) {
                return $default;
            }

            return unserialize($value);
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        try {
            $redis = $this->getRedis();
            $ttl = $this->normalizeTtl($ttl);

            $serialized = serialize($value);

            if ($ttl === null || $ttl <= 0) {
                return $redis->set($this->prefix . $key, $serialized);
            }

            return $redis->setex($this->prefix . $key, $ttl, $serialized);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        try {
            $redis = $this->getRedis();
            return $redis->del($this->prefix . $key) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        try {
            $redis = $this->getRedis();
            $pattern = $this->prefix . '*';
            $keys = $redis->keys($pattern);

            if (empty($keys)) {
                return true;
            }

            return $redis->del($keys) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        try {
            $redis = $this->getRedis();
            return $redis->exists($this->prefix . $key) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        try {
            $redis = $this->getRedis();
            $prefixedKeys = [];

            foreach ($keys as $key) {
                $prefixedKeys[] = $this->prefix . $key;
            }

            $values = $redis->mget($prefixedKeys);
            $result = [];

            $i = 0;
            foreach ($keys as $key) {
                $value = $values[$i++] ?? false;
                $result[$key] = $value === false ? $default : unserialize($value);
            }

            return $result;
        } catch (\Exception $e) {
            $result = [];
            foreach ($keys as $key) {
                $result[$key] = $default;
            }
            return $result;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        try {
            $redis = $this->getRedis();
            $ttl = $this->normalizeTtl($ttl);

            $redis->multi();

            foreach ($values as $key => $value) {
                $serialized = serialize($value);

                if ($ttl === null || $ttl <= 0) {
                    $redis->set($this->prefix . $key, $serialized);
                } else {
                    $redis->setex($this->prefix . $key, $ttl, $serialized);
                }
            }

            $results = $redis->exec();
            return !in_array(false, $results, true);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        try {
            $redis = $this->getRedis();
            $prefixedKeys = [];

            foreach ($keys as $key) {
                $prefixedKeys[] = $this->prefix . $key;
            }

            if (empty($prefixedKeys)) {
                return true;
            }

            return $redis->del($prefixedKeys) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Normalize TTL value
     */
    private function normalizeTtl(null|int|\DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return $this->defaultTtl;
        }

        if ($ttl instanceof \DateInterval) {
            $now = new \DateTime();
            $future = clone $now;
            $future->add($ttl);
            return $future->getTimestamp() - $now->getTimestamp();
        }

        return $ttl;
    }

    /**
     * Close Redis connection
     */
    public function __destruct()
    {
        if ($this->redis !== null) {
            try {
                $this->redis->close();
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
    }
}