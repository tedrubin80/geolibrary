<?php

declare(strict_types=1);

namespace GEOOptimizer\Cache;

/**
 * In-Memory Cache Implementation
 *
 * Simple in-memory caching for the current request only
 */
class MemoryCache implements CacheInterface
{
    /**
     * @var array<string, array{value: mixed, expiry: ?int}>
     */
    private array $cache = [];

    private int $defaultTtl;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->defaultTtl = $config['ttl'] ?? 3600;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->cache[$key])) {
            return $default;
        }

        $item = $this->cache[$key];

        // Check expiration
        if ($item['expiry'] !== null && $item['expiry'] < time()) {
            unset($this->cache[$key]);
            return $default;
        }

        return $item['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $ttl = $this->normalizeTtl($ttl);
        $expiry = time() + $ttl;

        $this->cache[$key] = [
            'value' => $value,
            'expiry' => $expiry
        ];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        unset($this->cache[$key]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        $item = $this->cache[$key];

        // Check expiration
        if ($item['expiry'] !== null && $item['expiry'] < time()) {
            unset($this->cache[$key]);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * Normalize TTL value
     */
    private function normalizeTtl(null|int|\DateInterval $ttl): int
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
     * Get current cache size
     */
    public function getSize(): int
    {
        return count($this->cache);
    }

    /**
     * Clean up expired entries
     */
    public function cleanup(): int
    {
        $cleaned = 0;
        $now = time();

        foreach ($this->cache as $key => $item) {
            if ($item['expiry'] !== null && $item['expiry'] < $now) {
                unset($this->cache[$key]);
                $cleaned++;
            }
        }

        return $cleaned;
    }
}