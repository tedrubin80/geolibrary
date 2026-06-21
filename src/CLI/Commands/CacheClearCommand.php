<?php

declare(strict_types=1);

namespace GEOOptimizer\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use GEOOptimizer\Cache\CacheManager;
use GEOOptimizer\Cache\FileCache;

/**
 * Clear cache command
 */
class CacheClearCommand extends Command
{
    protected static $defaultName = 'cache:clear';
    protected static $defaultDescription = 'Clear the GEO Optimizer cache';

    protected function configure(): void
    {
        $this
            ->setHelp('Clears all cached data from the GEO Optimizer')
            ->addOption(
                'adapter',
                'a',
                InputOption::VALUE_REQUIRED,
                'Cache adapter to clear (file, redis, memory, all)',
                'file'
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED,
                'Cache directory path (for file adapter)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force clear without confirmation'
            )
            ->addOption(
                'stats',
                's',
                InputOption::VALUE_NONE,
                'Show cache statistics before clearing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('GEO Optimizer - Cache Manager');

        $adapter = $input->getOption('adapter');
        $force = $input->getOption('force');

        try {
            // Show cache statistics if requested
            if ($input->getOption('stats')) {
                $this->showCacheStatistics($adapter, $input, $io);
            }

            // Confirm before clearing
            if (!$force) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    'Are you sure you want to clear the cache? (y/N) ',
                    false
                );

                if (!$helper->ask($input, $output, $question)) {
                    $io->warning('Cache clear cancelled.');
                    return Command::SUCCESS;
                }
            }

            $io->section('Clearing cache...');

            // Clear cache based on adapter
            $cleared = $this->clearCache($adapter, $input, $io);

            if ($cleared) {
                $io->success('Cache cleared successfully!');

                // Show post-clear statistics
                if ($input->getOption('stats')) {
                    $io->section('Cache Statistics (After Clear)');
                    $this->showCacheStatistics($adapter, $input, $io);
                }

                return Command::SUCCESS;
            } else {
                $io->error('Failed to clear cache');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error([
                'Cache operation failed',
                'Error: ' . $e->getMessage()
            ]);

            if ($io->isVerbose()) {
                $io->section('Stack Trace:');
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * Clear cache based on adapter
     */
    private function clearCache(string $adapter, InputInterface $input, SymfonyStyle $io): bool
    {
        switch ($adapter) {
            case 'file':
                return $this->clearFileCache($input, $io);

            case 'redis':
                return $this->clearRedisCache($io);

            case 'memory':
                $io->note('Memory cache is automatically cleared at the end of each request.');
                return true;

            case 'all':
                $success = true;
                $success = $this->clearFileCache($input, $io) && $success;
                $success = $this->clearRedisCache($io) && $success;
                $io->note('Memory cache is automatically cleared at the end of each request.');
                return $success;

            default:
                throw new \InvalidArgumentException("Unknown cache adapter: $adapter");
        }
    }

    /**
     * Clear file-based cache
     */
    private function clearFileCache(InputInterface $input, SymfonyStyle $io): bool
    {
        $path = $input->getOption('path') ?? sys_get_temp_dir() . '/geo_cache';

        $io->text("Clearing file cache at: $path");

        if (!is_dir($path)) {
            $io->warning("Cache directory does not exist: $path");
            return true;
        }

        $config = [
            'path' => $path,
            'prefix' => 'geo_'
        ];

        try {
            $cache = new FileCache($config);

            // First try to clean up expired entries
            if (method_exists($cache, 'cleanup')) {
                $cleaned = $cache->cleanup();
                $io->text("Cleaned up $cleaned expired entries");
            }

            // Then clear all
            $result = $cache->clear();

            if ($result) {
                $io->text('✅ File cache cleared');
            } else {
                $io->text('❌ Failed to clear file cache');
            }

            return $result;

        } catch (\Exception $e) {
            $io->error('Failed to clear file cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear Redis cache
     */
    private function clearRedisCache(SymfonyStyle $io): bool
    {
        if (!extension_loaded('redis')) {
            $io->note('Redis extension not installed, skipping Redis cache clear.');
            return true;
        }

        $io->text('Clearing Redis cache...');

        try {
            $config = [
                'host' => '127.0.0.1',
                'port' => 6379,
                'prefix' => 'geo_'
            ];

            // Try to connect to Redis
            $redis = new \Redis();
            if (!@$redis->connect($config['host'], $config['port'], 2.0)) {
                $io->warning('Could not connect to Redis server, skipping.');
                return true;
            }

            // Clear keys with our prefix
            $pattern = $config['prefix'] . '*';
            $keys = $redis->keys($pattern);

            if (empty($keys)) {
                $io->text('No Redis cache entries found');
                return true;
            }

            $deleted = $redis->del($keys);
            $io->text("✅ Cleared $deleted Redis cache entries");

            $redis->close();
            return true;

        } catch (\Exception $e) {
            $io->warning('Redis cache clear failed: ' . $e->getMessage());
            return true; // Don't fail the whole operation
        }
    }

    /**
     * Show cache statistics
     */
    private function showCacheStatistics(string $adapter, InputInterface $input, SymfonyStyle $io): void
    {
        $io->section('Cache Statistics');

        switch ($adapter) {
            case 'file':
                $this->showFileCacheStats($input, $io);
                break;

            case 'redis':
                $this->showRedisCacheStats($io);
                break;

            case 'all':
                $this->showFileCacheStats($input, $io);
                $this->showRedisCacheStats($io);
                break;

            default:
                $io->warning("Statistics not available for adapter: $adapter");
        }
    }

    /**
     * Show file cache statistics
     */
    private function showFileCacheStats(InputInterface $input, SymfonyStyle $io): void
    {
        $path = $input->getOption('path') ?? sys_get_temp_dir() . '/geo_cache';

        $io->text('<comment>File Cache:</comment>');

        if (!is_dir($path)) {
            $io->text('  Status: Directory does not exist');
            return;
        }

        $files = glob($path . '/geo_*');
        if ($files === false) {
            $files = [];
        }

        $totalSize = 0;
        $oldestFile = PHP_INT_MAX;
        $newestFile = 0;
        $expiredCount = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
                $mtime = filemtime($file);

                if ($mtime < $oldestFile) {
                    $oldestFile = $mtime;
                }
                if ($mtime > $newestFile) {
                    $newestFile = $mtime;
                }

                // Check if expired
                $content = file_get_contents($file);
                if ($content !== false) {
                    try {
                        $data = \GEOOptimizer\Cache\CacheSerializer::decode($content);
                    } catch (\GEOOptimizer\Exceptions\CacheException) {
                        continue;
                    }

                    if (is_array($data) && isset($data['expiry']) &&
                        $data['expiry'] !== null && $data['expiry'] < time()) {
                        $expiredCount++;
                    }
                }
            }
        }

        $io->text([
            '  Location: ' . $path,
            '  Total files: ' . count($files),
            '  Expired files: ' . $expiredCount,
            '  Total size: ' . $this->formatBytes($totalSize),
            '  Oldest entry: ' . ($oldestFile < PHP_INT_MAX ? date('Y-m-d H:i:s', $oldestFile) : 'N/A'),
            '  Newest entry: ' . ($newestFile > 0 ? date('Y-m-d H:i:s', $newestFile) : 'N/A')
        ]);
    }

    /**
     * Show Redis cache statistics
     */
    private function showRedisCacheStats(SymfonyStyle $io): void
    {
        $io->text('<comment>Redis Cache:</comment>');

        if (!extension_loaded('redis')) {
            $io->text('  Status: Extension not installed');
            return;
        }

        try {
            $redis = new \Redis();
            if (!@$redis->connect('127.0.0.1', 6379, 2.0)) {
                $io->text('  Status: Cannot connect to server');
                return;
            }

            $keys = $redis->keys('geo_*');
            $keyCount = count($keys);

            // Get memory usage
            $info = $redis->info('memory');
            $memoryUsed = $info['used_memory_human'] ?? 'N/A';

            $io->text([
                '  Server: 127.0.0.1:6379',
                '  GEO cache keys: ' . $keyCount,
                '  Memory used (total): ' . $memoryUsed
            ]);

            $redis->close();

        } catch (\Exception $e) {
            $io->text('  Status: ' . $e->getMessage());
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}