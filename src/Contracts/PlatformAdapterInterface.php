<?php

declare(strict_types=1);

namespace GEOOptimizer\Contracts;

/**
 * Interface for AI platform adapters
 */
interface PlatformAdapterInterface
{
    /**
     * Get the platform name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if the platform is available/configured
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Query the platform with a test prompt
     *
     * @param string $query The query to test
     * @param array<string, mixed> $options Query options
     * @return array<string, mixed> Response data
     */
    public function query(string $query, array $options = []): array;

    /**
     * Check if a domain/business is mentioned in responses
     *
     * @param string $identifier Domain or business name
     * @param array<string> $testQueries Queries to test
     * @return array<string, mixed> Mention analysis
     */
    public function checkMentions(string $identifier, array $testQueries): array;

    /**
     * Get platform-specific optimization recommendations
     *
     * @param array<string, mixed> $contentAnalysis Content analysis data
     * @return array<string, mixed> Recommendations
     */
    public function getOptimizationTips(array $contentAnalysis): array;
}
