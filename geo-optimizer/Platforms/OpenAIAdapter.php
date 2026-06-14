<?php

declare(strict_types=1);

namespace GEOOptimizer\Platforms;

/**
 * OpenAI/ChatGPT Platform Adapter
 *
 * Handles interactions with OpenAI's API for GEO analysis and citation tracking
 */
class OpenAIAdapter extends AbstractPlatformAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'OpenAI/ChatGPT';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEndpoint(): string
    {
        return 'https://api.openai.com/v1/chat/completions';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(): array
    {
        return [
            'model' => 'gpt-4o-mini',
            'max_tokens' => 1000,
            'temperature' => 0.7,
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

        // ChatGPT favors conversational, Q&A style content
        if (($contentAnalysis['structure_quality']['faq_questions'] ?? 0) < 3) {
            $tips[] = [
                'priority' => 'high',
                'category' => 'chatgpt_specific',
                'tip' => 'Add FAQ sections - ChatGPT excels at answering direct questions',
                'platform' => $this->getName()
            ];
        }

        // ChatGPT likes step-by-step instructions
        if (!($contentAnalysis['structure_quality']['has_lists'] ?? false)) {
            $tips[] = [
                'priority' => 'medium',
                'category' => 'chatgpt_specific',
                'tip' => 'Include numbered steps or bullet points for procedural content',
                'platform' => $this->getName()
            ];
        }

        // ChatGPT values recent, accurate information
        $tips[] = [
            'priority' => 'medium',
            'category' => 'chatgpt_specific',
            'tip' => 'Include dates and keep content updated - ChatGPT weights recency',
            'platform' => $this->getName()
        ];

        return $tips;
    }

    /**
     * Test if content would be cited by ChatGPT
     *
     * @param string $content The content to test
     * @param string $businessName The business name to check for
     * @return array<string, mixed>
     */
    public function testCitationPotential(string $content, string $businessName): array
    {
        if (!$this->isAvailable()) {
            return ['error' => 'API key not configured'];
        }

        // Create test queries that might surface this content
        $testQueries = [
            "What are the best {$businessName} services?",
            "Tell me about {$businessName}",
            "Who provides the best services like {$businessName}?"
        ];

        $results = [
            'content_length' => strlen($content),
            'test_queries' => $testQueries,
            'citation_analysis' => []
        ];

        // Analyze content structure for ChatGPT optimization
        $structureScore = 0;

        // Check for elements ChatGPT favors
        if (preg_match('/^#{1,3}\s+/m', $content)) $structureScore += 20;
        if (preg_match('/^\d+\.\s+/m', $content)) $structureScore += 15;
        if (preg_match('/\?$/m', $content)) $structureScore += 15;
        if (strlen($content) > 500) $structureScore += 10;
        if (preg_match('/\b(expert|certified|licensed|award)/i', $content)) $structureScore += 20;

        $results['chatgpt_optimization_score'] = min(100, $structureScore);
        $results['likely_to_cite'] = $structureScore >= 60;

        return $results;
    }
}
