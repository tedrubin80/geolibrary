<?php

declare(strict_types=1);

namespace GEOOptimizer\Analysis;

use GEOOptimizer\Exceptions\ValidationException;

/**
 * Analyze multiple pages or sites in one batch.
 */
class BulkSiteAnalyzer
{
    private ContentAnalyzer $contentAnalyzer;

    public function __construct(?ContentAnalyzer $contentAnalyzer = null)
    {
        $this->contentAnalyzer = $contentAnalyzer ?? new ContentAnalyzer();
    }

    /**
     * @param array<int, array{id?: string, content: string, metadata?: array<string, mixed>}> $items
     * @return array<string, mixed>
     */
    public function analyze(array $items): array
    {
        if ($items === []) {
            throw new ValidationException('At least one item is required for bulk analysis');
        }

        $results = [];
        $scores = [];

        foreach ($items as $index => $item) {
            $content = trim($item['content']);
            if ($content === '') {
                throw new ValidationException(sprintf('Item %d has empty content', $index));
            }

            $id = (string) ($item['id'] ?? 'item-' . ($index + 1));
            $metadata = is_array($item['metadata'] ?? null) ? $item['metadata'] : [];
            $analysis = $this->contentAnalyzer->analyzeForGEO($content, $metadata);

            $results[] = [
                'id' => $id,
                'overall_score' => $analysis['overall_score'] ?? 0,
                'word_count' => $analysis['word_count'] ?? 0,
                'recommendations' => array_slice($analysis['recommendations'] ?? [], 0, 3),
                'analysis' => $analysis,
            ];

            $scores[] = (float) ($analysis['overall_score'] ?? 0);
        }

        usort($results, static fn (array $a, array $b): int => $b['overall_score'] <=> $a['overall_score']);

        return [
            'count' => count($results),
            'average_score' => round(array_sum($scores) / count($scores), 1),
            'highest_score' => max($scores),
            'lowest_score' => min($scores),
            'results' => $results,
            'ranking' => array_column($results, 'id'),
        ];
    }
}
