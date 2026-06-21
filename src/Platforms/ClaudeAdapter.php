<?php

declare(strict_types=1);

namespace GEOOptimizer\Platforms;

/**
 * Anthropic Claude Platform Adapter
 *
 * Handles interactions with Claude's API for GEO analysis and citation tracking
 */
class ClaudeAdapter extends AbstractPlatformAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Anthropic Claude';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEndpoint(): string
    {
        return 'https://api.anthropic.com/v1/messages';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(): array
    {
        return [
            'model' => 'claude-3-haiku-20240307',
            'max_tokens' => 1000,
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
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, array $options = []): array
    {
        $payload = [
            'model' => $options['model'] ?? $this->config['model'],
            'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $query
                ]
            ]
        ];

        $response = $this->makeRequest($payload);

        $content = '';
        if (isset($response['content']) && is_array($response['content'])) {
            foreach ($response['content'] as $block) {
                if ($block['type'] === 'text') {
                    $content .= $block['text'];
                }
            }
        }

        return [
            'platform' => $this->getName(),
            'query' => $query,
            'content' => $content,
            'model' => $response['model'] ?? $this->config['model'],
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

        // Claude values nuanced, accurate information
        if (($contentAnalysis['authority_signals']['score'] ?? 0) < 70) {
            $tips[] = [
                'priority' => 'high',
                'category' => 'claude_specific',
                'tip' => 'Add nuanced details and caveats - Claude values accuracy over brevity',
                'platform' => $this->getName()
            ];
        }

        // Claude excels with well-structured, comprehensive content
        if (($contentAnalysis['completeness']['score'] ?? 0) < 80) {
            $tips[] = [
                'priority' => 'high',
                'category' => 'claude_specific',
                'tip' => 'Make content comprehensive - Claude prefers thorough explanations',
                'platform' => $this->getName()
            ];
        }

        // Claude appreciates ethical considerations
        $tips[] = [
            'priority' => 'medium',
            'category' => 'claude_specific',
            'tip' => 'Include ethical considerations and balanced perspectives when relevant',
            'platform' => $this->getName()
        ];

        // Claude values citations and sources
        $tips[] = [
            'priority' => 'medium',
            'category' => 'claude_specific',
            'tip' => 'Reference authoritative sources to increase credibility for Claude',
            'platform' => $this->getName()
        ];

        return $tips;
    }

    /**
     * Test if content aligns with Claude's preferences
     *
     * @param string $content The content to analyze
     * @return array<string, mixed>
     */
    public function analyzeClaudeCompatibility(string $content): array
    {
        $score = 0;
        $factors = [];

        // Claude values comprehensive content
        $wordCount = str_word_count($content);
        if ($wordCount > 500) {
            $score += 15;
            $factors['comprehensive'] = true;
        }

        // Claude appreciates nuance and caveats
        $nuanceWords = ['however', 'although', 'while', 'consider', 'important to note'];
        $nuanceCount = 0;
        foreach ($nuanceWords as $word) {
            if (stripos($content, $word) !== false) {
                $nuanceCount++;
            }
        }
        if ($nuanceCount >= 2) {
            $score += 20;
            $factors['nuanced'] = true;
        }

        // Claude values accurate, specific information
        if (preg_match_all('/\d+/', $content) > 3) {
            $score += 15;
            $factors['specific_data'] = true;
        }

        // Claude likes well-structured content
        if (preg_match('/^#{1,3}\s+/m', $content)) {
            $score += 15;
            $factors['structured'] = true;
        }

        // Claude appreciates citing sources
        if (preg_match('/(according to|source:|reference:|study shows)/i', $content)) {
            $score += 20;
            $factors['references_sources'] = true;
        }

        // Claude values ethical considerations
        if (preg_match('/(ethical|responsible|sustainable|transparent)/i', $content)) {
            $score += 15;
            $factors['ethical_awareness'] = true;
        }

        return [
            'claude_compatibility_score' => min(100, $score),
            'factors_present' => $factors,
            'recommendations' => $this->getClaudeRecommendations($factors)
        ];
    }

    /**
     * Get recommendations based on missing factors
     *
     * @param array<string, bool> $factors
     * @return array<string>
     */
    private function getClaudeRecommendations(array $factors): array
    {
        $recommendations = [];

        if (!($factors['comprehensive'] ?? false)) {
            $recommendations[] = 'Expand content to be more comprehensive (500+ words)';
        }
        if (!($factors['nuanced'] ?? false)) {
            $recommendations[] = 'Add nuance with words like "however", "although", "consider"';
        }
        if (!($factors['specific_data'] ?? false)) {
            $recommendations[] = 'Include specific numbers, statistics, or data points';
        }
        if (!($factors['structured'] ?? false)) {
            $recommendations[] = 'Add clear headings to organize content';
        }
        if (!($factors['references_sources'] ?? false)) {
            $recommendations[] = 'Reference authoritative sources or studies';
        }

        return $recommendations;
    }
}
