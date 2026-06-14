<?php

declare(strict_types=1);

namespace GEOOptimizer\Platforms;

/**
 * Perplexity AI Platform Adapter
 *
 * Handles interactions with Perplexity's API for GEO analysis and citation tracking
 */
class PerplexityAdapter extends AbstractPlatformAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Perplexity AI';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEndpoint(): string
    {
        return 'https://api.perplexity.ai/chat/completions';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(): array
    {
        return [
            'model' => 'llama-3.1-sonar-small-128k-online',
            'max_tokens' => 1000,
            'temperature' => 0.2,
            'timeout' => 30
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaders(): array
    {
        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, array $options = []): array
    {
        $payload = [
            'model' => $options['model'] ?? $this->config['model'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $query
                ]
            ],
            'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'],
            'temperature' => $options['temperature'] ?? $this->config['temperature']
        ];

        $response = $this->makeRequest($payload);

        return [
            'platform' => $this->getName(),
            'query' => $query,
            'content' => $response['choices'][0]['message']['content'] ?? '',
            'model' => $response['model'] ?? $this->config['model'],
            'citations' => $response['citations'] ?? [],
            'usage' => $response['usage'] ?? [],
            'timestamp' => time()
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPlatformSpecificTips(array $contentAnalysis): array
    {
        $tips = [];

        // Perplexity is search-focused, values SEO-style content
        $tips[] = [
            'priority' => 'high',
            'category' => 'perplexity_specific',
            'tip' => 'Optimize for search queries - Perplexity pulls from search results',
            'platform' => $this->getName()
        ];

        // Perplexity values citations and sources
        $tips[] = [
            'priority' => 'high',
            'category' => 'perplexity_specific',
            'tip' => 'Ensure content is indexed by search engines for Perplexity discovery',
            'platform' => $this->getName()
        ];

        // Perplexity likes definitive answers
        if (($contentAnalysis['citation_potential']['factors']['completeness'] ?? 0) < 70) {
            $tips[] = [
                'priority' => 'medium',
                'category' => 'perplexity_specific',
                'tip' => 'Provide definitive, direct answers to common questions',
                'platform' => $this->getName()
            ];
        }

        // Perplexity values fresh content
        $tips[] = [
            'priority' => 'medium',
            'category' => 'perplexity_specific',
            'tip' => 'Keep content fresh and updated - Perplexity favors recent sources',
            'platform' => $this->getName()
        ];

        return $tips;
    }

    /**
     * Analyze content for Perplexity optimization
     *
     * @param string $content The content to analyze
     * @param string $domain The domain hosting the content
     * @return array<string, mixed>
     */
    public function analyzePerplexityReadiness(string $content, string $domain): array
    {
        $score = 0;
        $factors = [];

        // Perplexity relies on search indexing
        $factors['needs_indexing'] = true;
        $score += 10; // Base score if content exists

        // Check for search-friendly elements
        if (preg_match('/^#{1,2}\s+/m', $content)) {
            $score += 15;
            $factors['has_headings'] = true;
        }

        // Definitive statements are preferred
        if (preg_match('/^(the|here are|these are|this is)/im', $content)) {
            $score += 15;
            $factors['definitive_statements'] = true;
        }

        // Lists are highly valued
        if (preg_match('/^\d+\.\s+|^[\*\-]\s+/m', $content)) {
            $score += 20;
            $factors['has_lists'] = true;
        }

        // Fresh dates indicate recency
        $currentYear = date('Y');
        if (strpos($content, $currentYear) !== false) {
            $score += 15;
            $factors['mentions_current_year'] = true;
        }

        // Statistics and data
        if (preg_match('/\d+%|\d+\s*(million|billion|thousand)/i', $content)) {
            $score += 15;
            $factors['has_statistics'] = true;
        }

        // Domain authority signals
        $factors['domain'] = $domain;

        return [
            'perplexity_readiness_score' => min(100, $score),
            'factors' => $factors,
            'recommendations' => $this->getPerplexityRecommendations($factors),
            'indexing_tips' => [
                'Submit sitemap to Google Search Console',
                'Ensure fast page load times',
                'Use semantic HTML markup',
                'Include meta descriptions'
            ]
        ];
    }

    /**
     * Get recommendations for Perplexity optimization
     *
     * @param array<string, mixed> $factors
     * @return array<string>
     */
    private function getPerplexityRecommendations(array $factors): array
    {
        $recommendations = [];

        if (!($factors['has_headings'] ?? false)) {
            $recommendations[] = 'Add clear H1/H2 headings for better search indexing';
        }
        if (!($factors['definitive_statements'] ?? false)) {
            $recommendations[] = 'Start sections with definitive statements like "Here are..." or "The best..."';
        }
        if (!($factors['has_lists'] ?? false)) {
            $recommendations[] = 'Include numbered or bulleted lists for structured information';
        }
        if (!($factors['mentions_current_year'] ?? false)) {
            $recommendations[] = 'Add current year references to signal content freshness';
        }
        if (!($factors['has_statistics'] ?? false)) {
            $recommendations[] = 'Include relevant statistics or data points';
        }

        return $recommendations;
    }
}
