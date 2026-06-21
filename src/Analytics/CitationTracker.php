<?php

declare(strict_types=1);

namespace GEOOptimizer\Analytics;

use GEOOptimizer\Contracts\TrackerInterface;
use GEOOptimizer\Exceptions\GEOException;
use GEOOptimizer\Platforms\OpenAIAdapter;
use GEOOptimizer\Platforms\ClaudeAdapter;
use GEOOptimizer\Platforms\PerplexityAdapter;
use GEOOptimizer\Platforms\GoogleAIAdapter;

/**
 * Citation Tracker
 *
 * Tracks and monitors AI citations of your content across different platforms.
 * Provides GEO Readiness scoring and manual citation logging capabilities.
 */
class CitationTracker implements TrackerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    private string $storagePath;

    /**
     * @var array<string, object>
     */
    private array $platformAdapters = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'storage_path' => sys_get_temp_dir() . '/geo_citations',
            'check_interval' => 3600,
            'max_checks_per_day' => 100,
            'notification_webhook' => null,
            'platforms' => [
                'openai' => ['enabled' => false, 'api_key' => ''],
                'claude' => ['enabled' => false, 'api_key' => ''],
                'perplexity' => ['enabled' => false, 'api_key' => ''],
                'google' => ['enabled' => false, 'api_key' => '']
            ]
        ], $config);

        $this->storagePath = $this->config['storage_path'];
        $this->initializeStorage();
        $this->initializePlatformAdapters();
    }

    /**
     * {@inheritdoc}
     */
    public function track(string $identifier, array $options = []): array
    {
        $this->validateIdentifier($identifier);

        $timestamp = time();
        $results = [
            'identifier' => $identifier,
            'timestamp' => $timestamp,
            'date' => date('Y-m-d H:i:s', $timestamp),
            'geo_readiness' => $this->calculateGEOReadiness($identifier, $options),
            'platform_analysis' => [],
            'manual_citations' => $this->getManualCitations($identifier),
            'recommendations' => []
        ];

        // Analyze each configured platform
        foreach ($this->platformAdapters as $name => $adapter) {
            if ($adapter->isAvailable()) {
                $results['platform_analysis'][$name] = $this->analyzePlatform(
                    $adapter,
                    $identifier,
                    $options
                );
            } else {
                $results['platform_analysis'][$name] = [
                    'available' => false,
                    'message' => 'API key not configured'
                ];
            }
        }

        // Generate recommendations
        $results['recommendations'] = $this->generateRecommendations($results);

        // Calculate summary metrics
        $results['summary'] = $this->calculateSummary($results);

        // Save tracking data
        $this->saveTrackingData($identifier, $results);

        // Send notifications if configured
        if ($this->config['notification_webhook'] && $this->shouldNotify($results)) {
            $this->sendNotification($identifier, $results);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getHistory(string $identifier, int $days = 30): array
    {
        $history = [];
        $startDate = time() - ($days * 86400);
        $files = $this->getTrackingFiles($identifier, $startDate);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $data = json_decode($content, true);
            if ($data && ($data['timestamp'] ?? 0) >= $startDate) {
                $history[] = [
                    'date' => $data['date'] ?? date('Y-m-d', $data['timestamp']),
                    'geo_readiness_score' => $data['geo_readiness']['overall_score'] ?? 0,
                    'manual_citations' => count($data['manual_citations'] ?? []),
                    'platforms_analyzed' => array_keys($data['platform_analysis'] ?? [])
                ];
            }
        }

        usort($history, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

        return $history;
    }

    /**
     * {@inheritdoc}
     */
    public function getInsights(string $identifier): array
    {
        $history = $this->getHistory($identifier, 30);

        if (empty($history)) {
            return [
                'status' => 'no_data',
                'message' => 'No tracking data available. Run track() first.'
            ];
        }

        $scores = array_column($history, 'geo_readiness_score');
        $citations = array_column($history, 'manual_citations');

        return [
            'identifier' => $identifier,
            'period_days' => 30,
            'data_points' => count($history),
            'geo_readiness' => [
                'current' => end($scores),
                'average' => round(array_sum($scores) / count($scores), 1),
                'trend' => $this->calculateTrend($scores),
                'high' => max($scores),
                'low' => min($scores)
            ],
            'citations' => [
                'total_logged' => array_sum($citations),
                'average_per_check' => round(array_sum($citations) / count($citations), 1)
            ],
            'recommendations' => $this->getInsightRecommendations($history)
        ];
    }

    /**
     * Log a manual citation discovery
     *
     * @param string $identifier Business/domain identifier
     * @param array<string, mixed> $citationData Citation details
     * @return bool Success status
     */
    public function logCitation(string $identifier, array $citationData): bool
    {
        $citation = array_merge([
            'timestamp' => time(),
            'date' => date('Y-m-d H:i:s'),
            'platform' => 'unknown',
            'query' => '',
            'context' => '',
            'url' => '',
            'screenshot' => '',
            'verified' => false
        ], $citationData);

        $filename = $this->getCitationLogFile($identifier);
        $existing = [];

        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            if ($content) {
                $existing = json_decode($content, true) ?? [];
            }
        }

        $existing[] = $citation;

        return file_put_contents(
            $filename,
            json_encode($existing, JSON_PRETTY_PRINT)
        ) !== false;
    }

    /**
     * Get manually logged citations
     *
     * @param string $identifier
     * @return array<int, array<string, mixed>>
     */
    public function getManualCitations(string $identifier): array
    {
        $filename = $this->getCitationLogFile($identifier);

        if (!file_exists($filename)) {
            return [];
        }

        $content = file_get_contents($filename);
        if (!$content) {
            return [];
        }

        return json_decode($content, true) ?? [];
    }

    /**
     * Calculate GEO Readiness score
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function calculateGEOReadiness(string $identifier, array $options): array
    {
        $content = $options['content'] ?? '';
        $metadata = $options['metadata'] ?? [];

        $factors = [
            'content_quality' => $this->assessContentQuality($content),
            'structure' => $this->assessStructure($content),
            'authority' => $this->assessAuthority($content, $metadata),
            'freshness' => $this->assessFreshness($metadata),
            'discoverability' => $this->assessDiscoverability($identifier, $metadata),
            'citation_signals' => $this->assessCitationSignals($content)
        ];

        $weights = [
            'content_quality' => 0.20,
            'structure' => 0.20,
            'authority' => 0.20,
            'freshness' => 0.10,
            'discoverability' => 0.15,
            'citation_signals' => 0.15
        ];

        $overallScore = 0;
        foreach ($factors as $factor => $score) {
            $overallScore += $score * ($weights[$factor] ?? 0);
        }

        return [
            'overall_score' => round($overallScore, 1),
            'factors' => $factors,
            'weights' => $weights,
            'grade' => $this->getGrade($overallScore),
            'interpretation' => $this->getScoreInterpretation($overallScore)
        ];
    }

    /**
     * Assess content quality
     */
    private function assessContentQuality(string $content): float
    {
        if (empty($content)) return 0;

        $score = 0;
        $wordCount = str_word_count($content);

        // Word count scoring
        if ($wordCount >= 300) $score += 20;
        if ($wordCount >= 500) $score += 10;
        if ($wordCount >= 1000) $score += 10;

        // Sentence variety
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);
        if ($sentenceCount > 0) {
            $avgLength = $wordCount / $sentenceCount;
            if ($avgLength >= 10 && $avgLength <= 20) $score += 20;
            elseif ($avgLength < 25) $score += 10;
        }

        // Readability indicators
        $complexWords = preg_match_all('/\b\w{10,}\b/', $content);
        $complexRatio = $complexWords / max(1, $wordCount);
        if ($complexRatio < 0.15) $score += 20;
        elseif ($complexRatio < 0.25) $score += 10;

        // Unique value indicators
        $uniqueIndicators = ['unique', 'exclusive', 'proprietary', 'original', 'first'];
        foreach ($uniqueIndicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                $score += 4;
            }
        }

        return min(100, $score);
    }

    /**
     * Assess content structure
     */
    private function assessStructure(string $content): float
    {
        if (empty($content)) return 0;

        $score = 0;

        // Headings
        if (preg_match('/^#{1,6}\s+/m', $content)) $score += 20;

        // Lists
        if (preg_match('/^[\*\-\+]\s+|^\d+\.\s+/m', $content)) $score += 20;

        // Paragraphs
        $paragraphs = preg_split('/\n\s*\n/', trim($content));
        if (count($paragraphs) >= 3) $score += 15;

        // Questions (FAQ style)
        if (preg_match('/\?$/m', $content)) $score += 15;

        // Short paragraphs (digestible)
        $avgParagraphLength = array_sum(array_map('strlen', $paragraphs)) / max(1, count($paragraphs));
        if ($avgParagraphLength < 500) $score += 15;

        // Clear sections
        if (preg_match('/^#{2}\s+/m', $content)) $score += 15;

        return min(100, $score);
    }

    /**
     * Assess authority signals
     *
     * @param array<string, mixed> $metadata
     */
    private function assessAuthority(string $content, array $metadata): float
    {
        $score = 0;

        // Credential keywords
        $credentialWords = [
            'certified', 'licensed', 'accredited', 'award', 'expert',
            'professional', 'specialist', 'years of experience', 'established'
        ];

        foreach ($credentialWords as $word) {
            if (stripos($content, $word) !== false) {
                $score += 10;
            }
        }

        // Statistics and data
        if (preg_match('/\d+%/', $content)) $score += 10;
        if (preg_match('/\d+\s*(years|clients|projects|customers)/i', $content)) $score += 10;

        // Source citations
        if (preg_match('/(according to|source:|study shows|research)/i', $content)) $score += 15;

        // Author information in metadata
        if (!empty($metadata['author'])) $score += 10;
        if (!empty($metadata['credentials'])) $score += 10;

        return min(100, $score);
    }

    /**
     * Assess content freshness
     *
     * @param array<string, mixed> $metadata
     */
    private function assessFreshness(array $metadata): float
    {
        if (empty($metadata['last_updated']) && empty($metadata['published_date'])) {
            return 50; // Neutral if no date info
        }

        $lastUpdated = strtotime($metadata['last_updated'] ?? $metadata['published_date']);
        if (!$lastUpdated) return 50;

        $daysSince = (time() - $lastUpdated) / 86400;

        if ($daysSince <= 7) return 100;
        if ($daysSince <= 30) return 90;
        if ($daysSince <= 90) return 75;
        if ($daysSince <= 180) return 60;
        if ($daysSince <= 365) return 40;

        return 20;
    }

    /**
     * Assess discoverability
     *
     * @param array<string, mixed> $metadata
     */
    private function assessDiscoverability(string $identifier, array $metadata): float
    {
        $score = 50; // Base score

        // Domain/site is indexed
        if ($metadata['indexed'] ?? false) $score += 20;

        // Has sitemap
        if ($metadata['has_sitemap'] ?? false) $score += 10;

        // Schema markup present
        if ($metadata['has_schema'] ?? false) $score += 15;

        // Social presence
        if (!empty($metadata['social_profiles'])) $score += 5;

        return min(100, $score);
    }

    /**
     * Assess citation signals in content
     */
    private function assessCitationSignals(string $content): float
    {
        if (empty($content)) return 0;

        $score = 0;

        // Definitive statements
        if (preg_match('/^(the|here are|these are|this is the)/im', $content)) $score += 15;

        // Specific data points
        $numbers = preg_match_all('/\d+/', $content);
        if ($numbers >= 3) $score += 15;
        if ($numbers >= 5) $score += 10;

        // Actionable content
        $actionWords = ['how to', 'step', 'guide', 'tutorial', 'method'];
        foreach ($actionWords as $word) {
            if (stripos($content, $word) !== false) {
                $score += 10;
            }
        }

        // Comprehensive coverage
        $wordCount = str_word_count($content);
        if ($wordCount >= 500) $score += 10;
        if ($wordCount >= 1000) $score += 10;

        // Expert terminology (domain-specific)
        if (preg_match('/\b(methodology|framework|analysis|implementation|optimization)\b/i', $content)) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * Analyze a specific platform
     *
     * @param object $adapter Platform adapter
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function analyzePlatform(object $adapter, string $identifier, array $options): array
    {
        $testQueries = $options['test_queries'] ?? $this->generateTestQueries($identifier, $options);

        return [
            'available' => true,
            'platform' => $adapter->getName(),
            'test_queries' => $testQueries,
            'optimization_tips' => $adapter->getOptimizationTips($options['content_analysis'] ?? []),
            'note' => 'For live citation checking, API queries would be performed here'
        ];
    }

    /**
     * Generate test queries for citation checking
     *
     * @param array<string, mixed> $options
     * @return array<string>
     */
    private function generateTestQueries(string $identifier, array $options): array
    {
        $industry = $options['industry'] ?? 'business';
        $location = $options['location'] ?? '';

        $queries = [
            "best {$industry} services" . ($location ? " in {$location}" : ''),
            "what is {$identifier}",
            "tell me about {$identifier}",
            "{$identifier} reviews",
            "top {$industry} providers" . ($location ? " near {$location}" : '')
        ];

        return $queries;
    }

    /**
     * Generate recommendations based on tracking results
     *
     * @param array<string, mixed> $results
     * @return array<array<string, mixed>>
     */
    private function generateRecommendations(array $results): array
    {
        $recommendations = [];
        $geoScore = $results['geo_readiness']['overall_score'] ?? 0;
        $factors = $results['geo_readiness']['factors'] ?? [];

        if ($geoScore < 60) {
            $recommendations[] = [
                'priority' => 'critical',
                'category' => 'overall',
                'message' => 'GEO Readiness score is low. Focus on improving content quality and structure.'
            ];
        }

        // Factor-specific recommendations
        if (($factors['content_quality'] ?? 0) < 60) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'content',
                'message' => 'Improve content depth and quality. Add more comprehensive information.'
            ];
        }

        if (($factors['structure'] ?? 0) < 60) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'structure',
                'message' => 'Add clear headings, lists, and FAQ sections for better AI parsing.'
            ];
        }

        if (($factors['authority'] ?? 0) < 60) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'authority',
                'message' => 'Include credentials, experience data, and social proof.'
            ];
        }

        if (($factors['freshness'] ?? 0) < 60) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'freshness',
                'message' => 'Update content regularly and include recent dates.'
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate summary metrics
     *
     * @param array<string, mixed> $results
     * @return array<string, mixed>
     */
    private function calculateSummary(array $results): array
    {
        $platformsConfigured = 0;
        $platformsAvailable = 0;

        foreach ($results['platform_analysis'] as $platform) {
            $platformsConfigured++;
            if ($platform['available'] ?? false) {
                $platformsAvailable++;
            }
        }

        return [
            'geo_readiness_score' => $results['geo_readiness']['overall_score'] ?? 0,
            'geo_readiness_grade' => $results['geo_readiness']['grade'] ?? 'N/A',
            'manual_citations_logged' => count($results['manual_citations'] ?? []),
            'platforms_configured' => $platformsConfigured,
            'platforms_available' => $platformsAvailable,
            'recommendations_count' => count($results['recommendations'] ?? []),
            'critical_issues' => count(array_filter(
                $results['recommendations'] ?? [],
                fn($r) => ($r['priority'] ?? '') === 'critical'
            ))
        ];
    }

    /**
     * Get grade from score
     */
    private function getGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 45) return 'D+';
        if ($score >= 40) return 'D';

        return 'F';
    }

    /**
     * Get score interpretation
     */
    private function getScoreInterpretation(float $score): string
    {
        if ($score >= 80) {
            return 'Excellent GEO readiness. Content is well-optimized for AI citations.';
        }
        if ($score >= 60) {
            return 'Good GEO readiness. Some improvements could increase citation potential.';
        }
        if ($score >= 40) {
            return 'Moderate GEO readiness. Significant improvements recommended.';
        }

        return 'Low GEO readiness. Major content and structure improvements needed.';
    }

    /**
     * Calculate trend from historical scores
     *
     * @param array<float> $scores
     */
    private function calculateTrend(array $scores): string
    {
        if (count($scores) < 2) return 'insufficient_data';

        $recentAvg = array_sum(array_slice($scores, -3)) / min(3, count($scores));
        $olderAvg = array_sum(array_slice($scores, 0, 3)) / min(3, count($scores));

        $change = $recentAvg - $olderAvg;

        if ($change > 5) return 'improving';
        if ($change < -5) return 'declining';

        return 'stable';
    }

    /**
     * Get insight-based recommendations
     *
     * @param array<array<string, mixed>> $history
     * @return array<string>
     */
    private function getInsightRecommendations(array $history): array
    {
        $recommendations = [];
        $scores = array_column($history, 'geo_readiness_score');

        if (empty($scores)) {
            return ['Start tracking to generate insights'];
        }

        $avg = array_sum($scores) / count($scores);
        $trend = $this->calculateTrend($scores);

        if ($avg < 60) {
            $recommendations[] = 'Focus on improving overall GEO readiness score';
        }

        if ($trend === 'declining') {
            $recommendations[] = 'Scores are declining - review recent content changes';
        }

        $citations = array_column($history, 'manual_citations');
        if (array_sum($citations) === 0) {
            $recommendations[] = 'Start logging citations when you find them in AI responses';
        }

        return $recommendations;
    }

    /**
     * Initialize storage directory
     */
    private function initializeStorage(): void
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    /**
     * Initialize platform adapters
     */
    private function initializePlatformAdapters(): void
    {
        $platforms = $this->config['platforms'] ?? [];

        if ($platforms['openai']['enabled'] ?? false) {
            $this->platformAdapters['openai'] = new OpenAIAdapter([
                'api_key' => $platforms['openai']['api_key'] ?? ''
            ]);
        }

        if ($platforms['claude']['enabled'] ?? false) {
            $this->platformAdapters['claude'] = new ClaudeAdapter([
                'api_key' => $platforms['claude']['api_key'] ?? ''
            ]);
        }

        if ($platforms['perplexity']['enabled'] ?? false) {
            $this->platformAdapters['perplexity'] = new PerplexityAdapter([
                'api_key' => $platforms['perplexity']['api_key'] ?? ''
            ]);
        }

        if ($platforms['google']['enabled'] ?? false) {
            $this->platformAdapters['google'] = new GoogleAIAdapter([
                'api_key' => $platforms['google']['api_key'] ?? ''
            ]);
        }
    }

    /**
     * Validate identifier
     */
    private function validateIdentifier(string $identifier): void
    {
        if (empty(trim($identifier))) {
            throw new GEOException('Identifier cannot be empty');
        }
    }

    /**
     * Get tracking files for identifier
     *
     * @return array<string>
     */
    private function getTrackingFiles(string $identifier, int $startDate): array
    {
        $hash = $this->getIdentifierHash($identifier);
        $pattern = "{$this->storagePath}/{$hash}_*.json";
        $files = glob($pattern);

        if ($files === false) {
            return [];
        }

        return array_filter($files, fn($file) => filemtime($file) >= $startDate);
    }

    /**
     * Save tracking data
     *
     * @param array<string, mixed> $data
     */
    private function saveTrackingData(string $identifier, array $data): void
    {
        $hash = $this->getIdentifierHash($identifier);
        $filename = "{$this->storagePath}/{$hash}_" . date('Y-m-d_His') . '.json';

        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Get citation log filename
     */
    private function getCitationLogFile(string $identifier): string
    {
        $hash = $this->getIdentifierHash($identifier);
        return "{$this->storagePath}/{$hash}_citations.json";
    }

    /**
     * Get identifier hash
     */
    private function getIdentifierHash(string $identifier): string
    {
        return md5(strtolower(trim($identifier)));
    }

    /**
     * Check if notification should be sent
     *
     * @param array<string, mixed> $results
     */
    private function shouldNotify(array $results): bool
    {
        // Notify on critical issues or significant score changes
        return ($results['summary']['critical_issues'] ?? 0) > 0;
    }

    /**
     * Send webhook notification
     *
     * @param array<string, mixed> $results
     */
    private function sendNotification(string $identifier, array $results): void
    {
        $webhook = $this->config['notification_webhook'] ?? '';
        if ($webhook === '' || !\GEOOptimizer\Http\UrlValidator::isAllowedWebhookUrl($webhook)) {
            return;
        }

        $payload = [
            'identifier' => $identifier,
            'geo_readiness_score' => $results['geo_readiness']['overall_score'] ?? 0,
            'critical_issues' => $results['summary']['critical_issues'] ?? 0,
            'timestamp' => time()
        ];

        $ch = curl_init($webhook);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 5,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
