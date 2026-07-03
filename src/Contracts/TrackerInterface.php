<?php

declare(strict_types=1);

namespace GEOOptimizer\Contracts;

/**
 * Interface for citation and mention trackers
 */
interface TrackerInterface
{
    /**
     * Track citations/mentions for a domain or business
     *
     * @param string $identifier Domain or business name
     * @param array<string, mixed> $options Tracking options
     * @return array<string, mixed> Tracking results
     */
    public function track(string $identifier, array $options = []): array;

    /**
     * Get historical tracking data
     *
     * @param string $identifier Domain or business name
     * @param int $days Number of days to look back
     * @return list<array<string, mixed>>
     */
    public function getHistory(string $identifier, int $days = 30): array;

    /**
     * Get insights from tracking data
     *
     * @param string $identifier Domain or business name
     * @return array<string, mixed> Insights
     */
    public function getInsights(string $identifier): array;
}
