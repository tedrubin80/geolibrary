<?php

declare(strict_types=1);

namespace GEOOptimizer;

use GEOOptimizer\LLMSTxt\Generator;
use GEOOptimizer\StructuredData\SchemaGenerator;
use GEOOptimizer\Analysis\ContentAnalyzer;
use GEOOptimizer\Templates\IndustryTemplateManager;
use GEOOptimizer\Analytics\CitationTracker;
use GEOOptimizer\Cache\CacheManager;
use GEOOptimizer\Cache\CacheInterface;
use GEOOptimizer\Exceptions\GEOException;

/**
 * Main GEO Optimizer class
 *
 * This is the primary entry point for the GEO Optimizer library.
 * It provides a unified interface for all GEO optimization features.
 */
class GEOOptimizer
{
    private array $config;
    private Generator $llmsTxtGenerator;
    private SchemaGenerator $schemaGenerator;
    private ContentAnalyzer $contentAnalyzer;
    private IndustryTemplateManager $templateManager;
    private CitationTracker $citationTracker;
    private CacheInterface $cache;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->initializeComponents();
    }

    /**
     * Generate llms.txt file for AI search engines
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
     * Analyze content for GEO optimization
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
     */
    public function getIndustryTemplate(string $industry): string
    {
        try {
            $templateData = $this->templateManager->getTemplate($industry);
        } catch (\Exception $e) {
            // Fallback to business template for unknown industries
            $templateData = $this->templateManager->getTemplate('business');
        }
        // Return the llms_template content as a string
        return $templateData['llms_template'] ?? $templateData['description'] ?? '';
    }

    /**
     * Track citations across AI platforms
     */
    public function trackCitations(string $businessName, array $platforms = []): array
    {
        return $this->citationTracker->track($businessName, $platforms);
    }

    /**
     * Perform complete GEO optimization
     */
    public function optimize(array $data): array
    {
        $results = [];

        try {
            // Generate llms.txt
            $results['llms_txt'] = $this->generateLLMSTxt($data);

            // Generate structured data
            $results['schema'] = $this->generateSchema($data['business_type'] ?? 'LocalBusiness', $data);

            // Analyze existing content
            if (isset($data['content'])) {
                $results['content_analysis'] = $this->analyzeContent($data['content']);
            }

            // Get industry template
            if (isset($data['industry'])) {
                $results['template'] = $this->getIndustryTemplate($data['industry']);
            }

            $results['status'] = 'success';
            $results['timestamp'] = date('c');

        } catch (\Exception $e) {
            throw new GEOException('Optimization failed: ' . $e->getMessage(), 0, $e);
        }

        return $results;
    }

    /**
     * Get current configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        // Reinitialize components with new config
        $this->initializeComponents();
    }

    /**
     * Get library version
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    private function initializeComponents(): void
    {
        $this->llmsTxtGenerator = new Generator($this->config);
        $this->schemaGenerator = new SchemaGenerator();
        $this->contentAnalyzer = new ContentAnalyzer($this->config['analysis'] ?? []);
        $this->templateManager = new IndustryTemplateManager();
        $this->citationTracker = new CitationTracker($this->config['tracking'] ?? []);
        $this->cache = CacheManager::getInstance($this->config['cache'] ?? []);
    }

    private function getDefaultConfig(): array
    {
        return [
            'templates_path' => __DIR__ . '/LLMSTxt/Templates',
            'cache_enabled' => false,  // Disabled by default for development
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
                'platforms' => ['chatgpt', 'claude', 'perplexity', 'google_ai'],
                'check_interval' => 24 // hours
            ]
        ];
    }

}