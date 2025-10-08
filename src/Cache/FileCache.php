<?php

declare(strict_types=1);

namespace GEOOptimizer\Cache;

use GEOOptimizer\Exceptions\CacheException;

/**
 * File-based Cache Implementation
 *
 * Simple file-based caching for development and small deployments
 */
class FileCache implements CacheInterface
{
    private string $path;
    private string $prefix;
    private int $defaultTtl;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->path = $config['path'] ?? sys_get_temp_dir() . '/geo_cache';
        $this->prefix = $config['prefix'] ?? 'geo_';
        $this->defaultTtl = $config['ttl'] ?? 3600;

        $this->ensureCacheDirectory();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return $default;
        }

        $content = file_get_contents($filename);
        if ($content === false) {
            return $default;
        }

        $data = unserialize($content);

        // Check expiration
        if ($data['expiry'] !== null && $data['expiry'] < time()) {
            unlink($filename);
            return $default;
        }

        return $data['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $filename = $this->getFilename($key);

        $ttl = $this->normalizeTtl($ttl);
        $expiry = $ttl === null ? null : time() + $ttl;

        $data = [
            'value' => $value,
            'expiry' => $expiry,
            'created' => time()
        ];

        $result = file_put_contents($filename, serialize($data), LOCK_EX);

        return $result !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return true;
        }

        return unlink($filename);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $files = glob($this->path . '/' . $this->prefix . '*');

        if ($files === false) {
            return true;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return false;
        }

        // Check if expired
        $content = file_get_contents($filename);
        if ($content === false) {
            return false;
        }

        $data = unserialize($content);

        if ($data['expiry'] !== null && $data['expiry'] < time()) {
            unlink($filename);
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
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Get cache filename for a key
     */
    private function getFilename(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) ?? $key;
        return $this->path . '/' . $this->prefix . $safeKey . '.cache';
    }

    /**
     * Ensure cache directory exists
     *
     * @throws CacheException
     */
    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->path)) {
            if (!mkdir($this->path, 0755, true) && !is_dir($this->path)) {
                throw new CacheException("Cannot create cache directory: {$this->path}");
            }
        }

        if (!is_writable($this->path)) {
            throw new CacheException("Cache directory is not writable: {$this->path}");
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
     * Clean up expired cache files
     */
    public function cleanup(): int
    {
        $files = glob($this->path . '/' . $this->prefix . '*');
        $cleaned = 0;

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = unserialize($content);

            if ($data['expiry'] !== null && $data['expiry'] < time()) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }
}