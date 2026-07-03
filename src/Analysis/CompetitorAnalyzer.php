<?php

declare(strict_types=1);

namespace GEOOptimizer\Analysis;

use GEOOptimizer\Exceptions\ValidationException;

/**
 * Compare a primary site against competitor content.
 */
class CompetitorAnalyzer
{
    private ContentAnalyzer $contentAnalyzer;

    public function __construct(?ContentAnalyzer $contentAnalyzer = null)
    {
        $this->contentAnalyzer = $contentAnalyzer ?? new ContentAnalyzer();
    }

    /**
     * @param array<string, mixed> $options
     * @param array<int, array{name: string, content: string, metadata?: array<string, mixed>}> $competitors
     * @return array<string, mixed>
     */
    public function compare(string $primaryName, string $primaryContent, array $competitors, array $options = []): array
    {
        $primaryContent = trim($primaryContent);
        if ($primaryContent === '') {
            throw new ValidationException('Primary content cannot be empty');
        }

        if ($competitors === []) {
            throw new ValidationException('At least one competitor is required');
        }

        $metadata = is_array($options['metadata'] ?? null) ? $options['metadata'] : [];
        $primaryAnalysis = $this->contentAnalyzer->analyzeForGEO($primaryContent, $metadata);
        $primaryScore = (float) ($primaryAnalysis['overall_score'] ?? 0);

        $entries = [[
            'name' => $primaryName,
            'role' => 'primary',
            'overall_score' => $primaryScore,
            'word_count' => $primaryAnalysis['word_count'] ?? 0,
            'analysis' => $primaryAnalysis,
        ]];

        foreach ($competitors as $index => $competitor) {
            $content = trim($competitor['content']);
            if ($content === '') {
                throw new ValidationException(sprintf('Competitor %d has empty content', $index));
            }

            $competitorMetadata = is_array($competitor['metadata'] ?? null)
                ? $competitor['metadata']
                : $metadata;

            $analysis = $this->contentAnalyzer->analyzeForGEO($content, $competitorMetadata);
            $entries[] = [
                'name' => $competitor['name'],
                'role' => 'competitor',
                'overall_score' => $analysis['overall_score'] ?? 0,
                'word_count' => $analysis['word_count'] ?? 0,
                'analysis' => $analysis,
            ];
        }

        usort($entries, static fn (array $a, array $b): int => $b['overall_score'] <=> $a['overall_score']);

        $leader = $entries[0];
        $primaryRank = $this->findRank($entries, $primaryName);
        $scoreGap = round($leader['overall_score'] - $primaryScore, 1);

        return [
            'primary' => [
                'name' => $primaryName,
                'score' => $primaryScore,
                'rank' => $primaryRank,
            ],
            'leader' => [
                'name' => $leader['name'],
                'score' => $leader['overall_score'],
            ],
            'score_gap' => $scoreGap,
            'ahead_of_primary' => $scoreGap > 0 && $leader['name'] !== $primaryName,
            'entries' => array_map(static function (array $entry): array {
                return [
                    'name' => $entry['name'],
                    'role' => $entry['role'],
                    'overall_score' => $entry['overall_score'],
                    'word_count' => $entry['word_count'],
                ];
            }, $entries),
            'recommendations' => $this->buildRecommendations($primaryAnalysis, $entries, $primaryName),
        ];
    }

    /**
     * @param list<array<string, mixed>> $entries
     */
    private function findRank(array $entries, string $primaryName): int
    {
        foreach ($entries as $index => $entry) {
            if ($entry['name'] === $primaryName) {
                return $index + 1;
            }
        }

        return count($entries);
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return list<string>
     */
    private function buildRecommendations(array $primaryAnalysis, array $entries, string $primaryName): array
    {
        $recommendations = array_slice($primaryAnalysis['recommendations'] ?? [], 0, 3);
        $leader = $entries[0];

        if ($leader['name'] !== $primaryName) {
            $recommendations[] = sprintf(
                '%s currently leads with a score of %s versus your %s.',
                $leader['name'],
                $leader['overall_score'],
                $primaryAnalysis['overall_score'] ?? 0
            );
        } else {
            $recommendations[] = 'Your site currently leads this comparison set.';
        }

        return array_values(array_unique($recommendations));
    }
}
