<?php

declare(strict_types=1);

namespace GEOOptimizer;

use GEOOptimizer\LLMSTxt\Generator;
use GEOOptimizer\StructuredData\SchemaGenerator;
use GEOOptimizer\Analysis\ContentAnalyzer;
use GEOOptimizer\Templates\IndustryTemplateManager;
use GEOOptimizer\Analytics\CitationTracker;
use GEOOptimizer\Analytics\GEOReadinessScore;
use GEOOptimizer\Cache\CacheManager;
use GEOOptimizer\Cache\CacheInterface;
use GEOOptimizer\Platforms\OpenAIAdapter;
use GEOOptimizer\Platforms\ClaudeAdapter;
use GEOOptimizer\Platforms\PerplexityAdapter;
use GEOOptimizer\Platforms\GoogleAIAdapter;
use GEOOptimizer\Exceptions\GEOException;

/**
 * Main GEO Optimizer class
 *
 * This is the primary entry point for the GEO Optimizer library.
 * It provides a unified interface for all GEO optimization features
 * to help websites rank better in AI-powered search engines.
 *
 * @version 2.0.0
 */
class GEOOptimizer
{
    public const VERSION = Version::VERSION;

    private array $config;
    private Generator $llmsTxtGenerator;
    private SchemaGenerator $schemaGenerator;
    private ContentAnalyzer $contentAnalyzer;
    private IndustryTemplateManager $templateManager;
    private CitationTracker $citationTracker;
    private CacheInterface $cache;

    /**
     * @var array<string, object>
     */
    private array $platformAdapters = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->initializeComponents();
        $this->initializePlatformAdapters();
    }

    /**
     * Generate llms.txt file for AI search engines
     *
     * @param array<string, mixed> $businessData
     * @return string
     */
    public function generateLLMSTxt(array $businessData): string
    {
        // Map common field names to expected format
        if (isset($businessData['business_name']) && !isset($businessData['name'])) {
            $businessData['name'] = $businessData['business_name'];
        }

        if ($this->config['cache_enabled'] ?? false) {
            $cacheKey = CacheManager::createKey('llms_txt', $businessData);

            return CacheManager::remember($cacheKey, function() use ($businessData) {
                return $this->llmsTxtGenerator->generate($businessData);
            }, $this->config['cache_ttl'] ?? 3600);
        }

        return $this->llmsTxtGenerator->generate($businessData);
    }

    /**
     * Generate structured data schema
     *
     * @param string $type Schema type (e.g., 'LocalBusiness', 'Restaurant', 'Event')
     * @param array<string, mixed> $data Schema data
     * @return array<string, mixed>
     */
    public function generateSchema(string $type, array $data): array
    {
        if ($this->config['cache_enabled'] ?? false) {
            $cacheKey = CacheManager::createKey("schema_{$type}", $data);

            return CacheManager::remember($cacheKey, function() use ($type, $data) {
                return $this->schemaGenerator->generate($type, $data);
            }, $this->config['cache_ttl'] ?? 3600);
        }

        return $this->schemaGenerator->generate($type, $data);
    }

    /**
     * Generate multiple schemas combined into a single JSON-LD
     *
     * @param array<array{type: string, data: array<string, mixed>}> $schemas
     * @return string JSON-LD string with @graph
     */
    public function generateMultipleSchemas(array $schemas): string
    {
        return $this->schemaGenerator->generateMultiple($schemas);
    }

    /**
     * Get supported schema types
     *
     * @return array<string>
     */
    public function getSupportedSchemaTypes(): array
    {
        return $this->schemaGenerator->getSupportedTypes();
    }

    /**
     * Analyze content for GEO optimization
     *
     * @param string $content Content to analyze
     * @param array<string, mixed> $options Analysis options
     * @return array<string, mixed>
     */
    public function analyzeContent(string $content, array $options = []): array
    {
        if ($this->config['cache_enabled'] ?? false) {
            $cacheKey = CacheManager::createKey('content_analysis', [
                'content_hash' => md5($content),
                'options' => $options
            ]);

            return CacheManager::remember($cacheKey, function() use ($content, $options) {
                return $this->contentAnalyzer->analyzeForGEO($content, $options);
            }, $this->config['cache_ttl'] ?? 1800);
        }

        return $this->contentAnalyzer->analyzeForGEO($content, $options);
    }

    /**
     * Get industry-specific template
     *
     * @param string $industry Industry name
     * @return string Template content
     */
    public function getIndustryTemplate(string $industry): string
    {
        try {
            $templateData = $this->templateManager->getTemplate($industry);
        } catch (\Exception $e) {
            // Fallback to business template for unknown industries
            $templateData = $this->templateManager->getTemplate('business');
        }
        return $templateData['llms_template'] ?? $templateData['description'] ?? '';
    }

    /**
     * Get all available industry templates
     *
     * @return array<string>
     */
    public function getAvailableIndustries(): array
    {
        return $this->templateManager->getAvailableIndustries();
    }

    /**
     * Generate JSON-LD structured data markup for embedding in HTML.
     *
     * @param array<string, mixed> $businessData
     */
    public function generateStructuredData(array $businessData): string
    {
        $data = $this->normalizeBusinessData($businessData);
        $schemaType = $this->resolveSchemaType($data);
        $schema = $this->generateSchema($schemaType, $data);
        $jsonLd = $this->schemaGenerator->toJsonLd($schema);

        return '<script type="application/ld+json">' . $jsonLd . '</script>' . PHP_EOL;
    }

    /**
     * Track citations and GEO readiness for a business/domain
     *
     * @param string $identifier Business name or domain
     * @param array<string, mixed> $options Tracking options
     * @return array<string, mixed>
     */
    public function trackCitations(string $identifier, array $options = []): array
    {
        return $this->citationTracker->track($identifier, $options);
    }

    /**
     * Log a manual citation discovery
     *
     * @param string $identifier Business name or domain
     * @param array<string, mixed> $citationData Citation details
     * @return bool
     */
    public function logCitation(string $identifier, array $citationData): bool
    {
        return $this->citationTracker->logCitation($identifier, $citationData);
    }

    /**
     * Get citation tracking history
     *
     * @param string $identifier Business name or domain
     * @param int $days Number of days to look back
     * @return list<array<string, mixed>>
     */
    public function getCitationHistory(string $identifier, int $days = 30): array
    {
        return $this->citationTracker->getHistory($identifier, $days);
    }

    /**
     * Get citation insights
     *
     * @param string $identifier Business name or domain
     * @return array<string, mixed>
     */
    public function getCitationInsights(string $identifier): array
    {
        return $this->citationTracker->getInsights($identifier);
    }

    /**
     * Get platform-specific optimization recommendations
     *
     * @param string $platform Platform name (openai, claude, perplexity, google)
     * @param array<string, mixed> $contentAnalysis Content analysis results
     * @return array<string, mixed>
     */
    public function getPlatformOptimizationTips(string $platform, array $contentAnalysis): array
    {
        $platform = strtolower($platform);

        if (!isset($this->platformAdapters[$platform])) {
            return [
                'error' => "Platform '{$platform}' not configured or unavailable",
                'available_platforms' => array_keys($this->platformAdapters)
            ];
        }

        return $this->platformAdapters[$platform]->getOptimizationTips($contentAnalysis);
    }

    /**
     * Get optimization tips for all configured platforms
     *
     * @param array<string, mixed> $contentAnalysis Content analysis results
     * @return array<string, array<string, mixed>>
     */
    public function getAllPlatformOptimizationTips(array $contentAnalysis): array
    {
        $tips = [];

        foreach ($this->platformAdapters as $name => $adapter) {
            $tips[$name] = $adapter->getOptimizationTips($contentAnalysis);
        }

        return $tips;
    }

    /**
     * Perform complete GEO optimization analysis
     *
     * @param array<string, mixed> $data Business and content data
     * @return array<string, mixed>
     */
    public function optimize(array $data): array
    {
        $results = [];

        try {
            // Generate llms.txt
            $results['llms_txt'] = $this->generateLLMSTxt($data);

            // Generate structured data
            $schemaType = $data['business_type'] ?? $data['schema_type'] ?? 'LocalBusiness';
            $results['schema'] = $this->generateSchema($schemaType, $data);
            $results['schema_json_ld'] = $this->schemaGenerator->toJsonLd($results['schema']);

            // Analyze existing content
            if (isset($data['content'])) {
                $results['content_analysis'] = $this->analyzeContent($data['content'], [
                    'business_type' => $schemaType
                ]);

                // Get platform-specific tips
                $results['platform_tips'] = $this->getAllPlatformOptimizationTips($results['content_analysis']);
            }

            // Get industry template
            if (isset($data['industry'])) {
                $results['template'] = $this->getIndustryTemplate($data['industry']);
            }

            // Track citations if identifier provided
            if (isset($data['identifier']) || isset($data['business_name'])) {
                $identifier = $data['identifier'] ?? $data['business_name'];
                $results['citation_tracking'] = $this->trackCitations($identifier, [
                    'content' => $data['content'] ?? '',
                    'industry' => $data['industry'] ?? 'business',
                    'location' => $data['city'] ?? $data['location'] ?? '',
                    'content_analysis' => $results['content_analysis'] ?? []
                ]);
            }

            $results['status'] = 'success';
            $results['timestamp'] = date('c');
            $results['version'] = self::VERSION;

        } catch (\Exception $e) {
            throw new GEOException('Optimization failed: ' . $e->getMessage(), 0, $e);
        }

        return $results;
    }

    /**
     * Quick GEO score check for content
     *
     * @param string $content Content to score
     * @return array<string, mixed>
     */
    public function quickScore(string $content): array
    {
        $analysis = $this->analyzeContent($content);

        return [
            'overall_score' => $analysis['overall_score'] ?? 0,
            'grade' => $this->getGrade($analysis['overall_score'] ?? 0),
            'key_metrics' => [
                'readability' => $analysis['readability']['score'] ?? 0,
                'authority' => $analysis['authority_signals']['score'] ?? 0,
                'structure' => $analysis['structure_quality']['score'] ?? 0,
                'citation_potential' => $analysis['citation_potential']['score'] ?? 0
            ],
            'top_recommendations' => array_slice($analysis['improvements'] ?? [], 0, 3)
        ];
    }

    /**
     * Generate FAQ schema from questions and answers
     *
     * @param array<int, array{question: string, answer: string}> $faqs
     * @return array<string, mixed>
     */
    public function generateFAQSchema(array $faqs): array
    {
        return $this->schemaGenerator->generateFAQ($faqs);
    }

    /**
     * Generate HowTo schema for instructional content
     *
     * @param array<string, mixed> $data HowTo data
     * @return array<string, mixed>
     */
    public function generateHowToSchema(array $data): array
    {
        return $this->schemaGenerator->generateHowTo($data);
    }

    /**
     * Generate Article schema
     *
     * @param array<string, mixed> $data Article data
     * @return array<string, mixed>
     */
    public function generateArticleSchema(array $data): array
    {
        return $this->schemaGenerator->generateArticle($data);
    }

    /**
     * Get current configuration
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set configuration
     *
     * @param array<string, mixed> $config
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->initializeComponents();
        $this->initializePlatformAdapters();
    }

    /**
     * Get library version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Get available platform adapters
     *
     * @return array<string, bool>
     */
    public function getAvailablePlatforms(): array
    {
        $platforms = [];

        foreach ($this->platformAdapters as $name => $adapter) {
            $platforms[$name] = $adapter->isAvailable();
        }

        return $platforms;
    }

    /**
     * Clear all caches
     */
    public function clearCache(): void
    {
        $this->cache->clear();
    }

    /**
     * @param array<int, array{id?: string, content: string, metadata?: array<string, mixed>}> $items
     * @return array<string, mixed>
     */
    public function bulkAnalyze(array $items): array
    {
        return (new \GEOOptimizer\Analysis\BulkSiteAnalyzer($this->contentAnalyzer))->analyze($items);
    }

    /**
     * @param array<int, array{name: string, content: string, metadata?: array<string, mixed>}> $competitors
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function compareCompetitors(
        string $primaryName,
        string $primaryContent,
        array $competitors,
        array $options = []
    ): array {
        return (new \GEOOptimizer\Analysis\CompetitorAnalyzer($this->contentAnalyzer))
            ->compare($primaryName, $primaryContent, $competitors, $options);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCitationDashboard(string $identifier, int $days = 30): array
    {
        return (new \GEOOptimizer\Analytics\CitationDashboard($this->citationTracker))
            ->getDashboard($identifier, $days);
    }

    /**
     * @return list<string>
     */
    public function listTrackedIdentifiers(): array
    {
        return (new \GEOOptimizer\Analytics\CitationDashboard($this->citationTracker))
            ->listIdentifiers();
    }

    /**
     * Initialize components
     */
    private function initializeComponents(): void
    {
        $this->llmsTxtGenerator = new Generator($this->config);
        $this->schemaGenerator = new SchemaGenerator();
        $this->contentAnalyzer = new ContentAnalyzer($this->config['analysis'] ?? []);
        $this->templateManager = new IndustryTemplateManager();
        $this->citationTracker = new CitationTracker($this->config['tracking'] ?? []);
        $this->cache = CacheManager::getInstance($this->config['cache'] ?? []);
    }

    /**
     * Initialize platform adapters
     */
    private function initializePlatformAdapters(): void
    {
        $platforms = $this->config['platforms'] ?? [];

        // OpenAI/ChatGPT
        $this->platformAdapters['openai'] = new OpenAIAdapter([
            'api_key' => $platforms['openai']['api_key'] ?? ''
        ]);

        // Claude
        $this->platformAdapters['claude'] = new ClaudeAdapter([
            'api_key' => $platforms['claude']['api_key'] ?? ''
        ]);

        // Perplexity
        $this->platformAdapters['perplexity'] = new PerplexityAdapter([
            'api_key' => $platforms['perplexity']['api_key'] ?? ''
        ]);

        // Google AI
        $this->platformAdapters['google'] = new GoogleAIAdapter([
            'api_key' => $platforms['google']['api_key'] ?? ''
        ]);
    }

    /**
     * @param array<string, mixed> $businessData
     * @return array<string, mixed>
     */
    private function normalizeBusinessData(array $businessData): array
    {
        $data = $businessData;

        if (isset($data['business_name']) && !isset($data['name'])) {
            $data['name'] = $data['business_name'];
        }

        if (!empty($data['location']) && empty($data['address'])) {
            $data['address'] = $data['location'];
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveSchemaType(array $data): string
    {
        if (!empty($data['schema_type'])) {
            return (string) $data['schema_type'];
        }

        if (!empty($data['business_type'])) {
            return (string) $data['business_type'];
        }

        if (!empty($data['industry'])) {
            try {
                $industrySchema = $this->templateManager->getIndustrySchema($data, (string) $data['industry']);

                return (string) $industrySchema['primary_schema'];
            } catch (\Exception $e) {
                // Fall through to LocalBusiness.
            }
        }

        return 'LocalBusiness';
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
     * Get default configuration
     *
     * @return array<string, mixed>
     */
    private function getDefaultConfig(): array
    {
        return [
            'templates_path' => __DIR__ . '/LLMSTxt/Templates',
            'cache_enabled' => false,
            'cache_ttl' => 3600,
            'cache' => [
                'adapter' => 'file',
                'path' => sys_get_temp_dir() . '/geo_cache',
                'prefix' => 'geo_'
            ],
            'analysis' => [
                'min_word_count' => 300,
                'target_keyword_density' => 0.02,
                'enable_readability' => true
            ],
            'tracking' => [
                'storage_path' => sys_get_temp_dir() . '/geo_citations',
                'platforms' => [
                    'openai' => ['enabled' => false, 'api_key' => ''],
                    'claude' => ['enabled' => false, 'api_key' => ''],
                    'perplexity' => ['enabled' => false, 'api_key' => ''],
                    'google' => ['enabled' => false, 'api_key' => '']
                ],
                'check_interval' => 24 // hours
            ],
            'platforms' => [
                'openai' => ['api_key' => ''],
                'claude' => ['api_key' => ''],
                'perplexity' => ['api_key' => ''],
                'google' => ['api_key' => '']
            ]
        ];
    }
}
