<?php

declare(strict_types=1);

namespace GEOOptimizer\Contracts;

/**
 * Interface for content analyzers
 */
interface AnalyzerInterface
{
    /**
     * Analyze content for GEO optimization
     *
     * @param string $content The content to analyze
     * @param array<string, mixed> $options Analysis options
     * @return array<string, mixed> Analysis results
     */
    public function analyze(string $content, array $options = []): array;

    /**
     * Get the analyzer's score weight
     *
     * @return float Weight between 0 and 1
     */
    public function getWeight(): float;

    /**
     * Get the analyzer name
     *
     * @return string
     */
    public function getName(): string;
}
