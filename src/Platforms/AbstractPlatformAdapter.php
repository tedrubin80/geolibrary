<?php

declare(strict_types=1);

namespace GEOOptimizer\Platforms;

use GEOOptimizer\Contracts\PlatformAdapterInterface;
use GEOOptimizer\Exceptions\GEOException;

/**
 * Abstract base class for AI platform adapters
 */
abstract class AbstractPlatformAdapter implements PlatformAdapterInterface
{
    protected string $apiKey;
    protected array $config;
    protected ?object $httpClient = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->apiKey = $config['api_key'] ?? '';
    }

    /**
     * Get default configuration
     *
     * @return array<string, mixed>
     */
    abstract protected function getDefaultConfig(): array;

    /**
     * Get the API endpoint
     *
     * @return string
     */
    abstract protected function getEndpoint(): string;

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * {@inheritdoc}
     */
    public function checkMentions(string $identifier, array $testQueries): array
    {
        if (!$this->isAvailable()) {
            return [
                'platform' => $this->getName(),
                'available' => false,
                'error' => 'API key not configured'
            ];
        }

        $results = [
            'platform' => $this->getName(),
            'identifier' => $identifier,
            'queries_tested' => count($testQueries),
            'mentions_found' => 0,
            'mention_details' => [],
            'timestamp' => time()
        ];

        foreach ($testQueries as $query) {
            try {
                $response = $this->query($query);
                $mentionAnalysis = $this->analyzeMentions($response, $identifier);

                if ($mentionAnalysis['found']) {
                    $results['mentions_found']++;
                    $results['mention_details'][] = [
                        'query' => $query,
                        'mention_type' => $mentionAnalysis['type'],
                        'context' => $mentionAnalysis['context'],
                        'confidence' => $mentionAnalysis['confidence']
                    ];
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'query' => $query,
                    'error' => $e->getMessage()
                ];
            }
        }

        $results['mention_rate'] = $results['queries_tested'] > 0
            ? ($results['mentions_found'] / $results['queries_tested']) * 100
            : 0;

        return $results;
    }

    /**
     * Analyze response for mentions of identifier
     *
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    protected function analyzeMentions(array $response, string $identifier): array
    {
        $content = $response['content'] ?? '';
        $identifierLower = strtolower($identifier);
        $contentLower = strtolower($content);

        $found = strpos($contentLower, $identifierLower) !== false;

        if (!$found) {
            return ['found' => false];
        }

        // Determine mention type
        $type = 'indirect';
        if (preg_match('/\b' . preg_quote($identifier, '/') . '\b/i', $content)) {
            $type = 'direct';
        }

        // Extract context around mention
        $position = strpos($contentLower, $identifierLower);
        $start = max(0, $position - 100);
        $context = substr($content, $start, 200);

        return [
            'found' => true,
            'type' => $type,
            'context' => $context,
            'confidence' => $type === 'direct' ? 0.95 : 0.7
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptimizationTips(array $contentAnalysis): array
    {
        $tips = [];
        $overallScore = $contentAnalysis['overall_score'] ?? 0;

        // Generic tips based on content analysis
        if ($overallScore < 60) {
            $tips[] = [
                'priority' => 'high',
                'category' => 'content_quality',
                'tip' => 'Improve overall content quality to increase citation likelihood',
                'platform' => $this->getName()
            ];
        }

        // Structure tips
        if (($contentAnalysis['structure_quality']['score'] ?? 0) < 70) {
            $tips[] = [
                'priority' => 'medium',
                'category' => 'structure',
                'tip' => 'Add more headings, lists, and clear sections for better AI parsing',
                'platform' => $this->getName()
            ];
        }

        // Authority tips
        if (($contentAnalysis['authority_signals']['score'] ?? 0) < 60) {
            $tips[] = [
                'priority' => 'high',
                'category' => 'authority',
                'tip' => 'Include credentials, experience, and social proof',
                'platform' => $this->getName()
            ];
        }

        return array_merge($tips, $this->getPlatformSpecificTips($contentAnalysis));
    }

    /**
     * Get platform-specific optimization tips
     *
     * @param array<string, mixed> $contentAnalysis
     * @return array<array<string, mixed>>
     */
    abstract protected function getPlatformSpecificTips(array $contentAnalysis): array;

    /**
     * Make HTTP request to API
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function makeRequest(array $payload): array
    {
        $ch = curl_init($this->getEndpoint());

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $this->getHeaders(),
            CURLOPT_TIMEOUT => $this->config['timeout'] ?? 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new GEOException("API request failed: {$error}");
        }

        if ($httpCode >= 400) {
            throw new GEOException("API returned error code: {$httpCode}");
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new GEOException("Invalid JSON response from API");
        }

        return $decoded;
    }

    /**
     * Get request headers
     *
     * @return array<string>
     */
    abstract protected function getHeaders(): array;
}
