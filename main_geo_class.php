<?php

namespace GEOOptimizer;

use GEOOptimizer\LLMSTxt\Generator;
use GEOOptimizer\StructuredData\SchemaGenerator;
use GEOOptimizer\Analysis\ContentAnalyzer;
use GEOOptimizer\Templates\IndustryTemplateManager;
use GEOOptimizer\Analytics\CitationTracker;
use GEOOptimizer\Exceptions\GEOException;

/**
 * Main GEO Optimizer class
 * 
 * This is the primary entry point for the GEO Optimizer library.
 * It provides a unified interface for all GEO optimization features.
 */
class GEOOptimizer
{
    private $config;
    private $llmsTxtGenerator;
    private $schemaGenerator;
    private $contentAnalyzer;
    private $templateManager;
    private $citationTracker;

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
        return $this->llmsTxtGenerator->generate($businessData);
    }

    /**
     * Generate structured data schema
     */
    public function generateSchema(string $type, array $data): array
    {
        return $this->schemaGenerator->generate($type, $data);
    }

    /**
     * Analyze content for GEO optimization
     */
    public function analyzeContent(string $content, array $options = []): array
    {
        return $this->contentAnalyzer->analyze($content, $options);
    }

    /**
     * Get industry-specific template
     */
    public function getIndustryTemplate(string $industry): string
    {
        return $this->templateManager->getTemplate($industry);
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

    private function initializeComponents(): void
    {
        $this->llmsTxtGenerator = new Generator($this->config['templates_path'] ?? null);
        $this->schemaGenerator = new SchemaGenerator();
        $this->contentAnalyzer = new ContentAnalyzer($this->config['analysis'] ?? []);
        $this->templateManager = new IndustryTemplateManager();
        $this->citationTracker = new CitationTracker($this->config['tracking'] ?? []);
    }

    private function getDefaultConfig(): array
    {
        return [
            'templates_path' => __DIR__ . '/LLMSTxt/Templates',
            'cache_enabled' => true,
            'cache_ttl' => 3600,
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

    /**
     * Get current configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Update configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->initializeComponents();
    }

    /**
     * Get version information
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }
}