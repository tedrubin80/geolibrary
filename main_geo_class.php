<?php

namespace GEOOptimizer;

use GEOOptimizer\LLMSTxt\Generator as LLMSTxtGenerator;
use GEOOptimizer\StructuredData\SchemaGenerator;
use GEOOptimizer\ContentOptimizer\AIContentStructure;
use GEOOptimizer\ContentOptimizer\MetaOptimizer;
use GEOOptimizer\Templates\Components\ServiceCard;
use GEOOptimizer\Templates\Components\FAQSection;
use GEOOptimizer\Exceptions\GEOException;

/**
 * Main GEO Optimizer Class
 * 
 * Provides comprehensive Generative Engine Optimization tools
 * for websites to improve visibility in AI-powered search engines.
 */
class GEOOptimizer
{
    private $config;
    private $llmsTxtGenerator;
    private $schemaGenerator;
    private $contentOptimizer;
    private $metaOptimizer;

    /**
     * Initialize the GEO Optimizer
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->initializeComponents();
    }

    /**
     * Generate complete GEO optimization for a website
     *
     * @param array $businessData Business information
     * @return array Complete optimization package
     */
    public function optimize(array $businessData): array
    {
        $this->validateBusinessData($businessData);

        return [
            'llms_txt' => $this->generateLLMSTxt($businessData),
            'structured_data' => $this->generateStructuredData($businessData),
            'meta_optimization' => $this->optimizeMeta($businessData),
            'content_structure' => $this->optimizeContent($businessData),
            'components' => $this->generateComponents($businessData)
        ];
    }

    /**
     * Generate llms.txt file content
     *
     * @param array $businessData Business information
     * @return string llms.txt content
     */
    public function generateLLMSTxt(array $businessData): string
    {
        return $this->llmsTxtGenerator->generate($businessData);
    }

    /**
     * Generate structured data markup
     *
     * @param array $businessData Business information
     * @param string $type Schema type (LocalBusiness, Organization, etc.)
     * @return string JSON-LD structured data
     */
    public function generateStructuredData(array $businessData, string $type = 'LocalBusiness'): string
    {
        return $this->schemaGenerator->generate($type, $businessData);
    }

    /**
     * Optimize content structure for AI
     *
     * @param array $contentData Content information
     * @return array Optimized content structure
     */
    public function optimizeContent(array $contentData): array
    {
        return $this->contentOptimizer->optimize($contentData);
    }

    /**
     * Generate AI-optimized meta tags
     *
     * @param array $pageData Page information
     * @return array Meta tag data
     */
    public function optimizeMeta(array $pageData): array
    {
        return $this->metaOptimizer->optimize($pageData);
    }

    /**
     * Generate Bootstrap components optimized for GEO
     *
     * @param array $businessData Business information
     * @return array Component HTML
     */
    public function generateComponents(array $businessData): array
    {
        $components = [];

        if (isset($businessData['services'])) {
            $components['service_cards'] = ServiceCard::generate($businessData['services']);
        }

        if (isset($businessData['faqs'])) {
            $components['faq_section'] = FAQSection::generate($businessData['faqs']);
        }

        return $components;
    }

    /**
     * Validate business data structure
     *
     * @param array $businessData
     * @throws GEOException
     */
    private function validateBusinessData(array $businessData): void
    {
        $required = ['name', 'description', 'industry'];
        
        foreach ($required as $field) {
            if (!isset($businessData[$field]) || empty($businessData[$field])) {
                throw new GEOException("Required field '{$field}' is missing or empty");
            }
        }
    }

    /**
     * Initialize library components
     */
    private function initializeComponents(): void
    {
        $this->llmsTxtGenerator = new LLMSTxtGenerator($this->config);
        $this->schemaGenerator = new SchemaGenerator($this->config);
        $this->contentOptimizer = new AIContentStructure($this->config);
        $this->metaOptimizer = new MetaOptimizer($this->config);
    }

    /**
     * Get default configuration
     *
     * @return array Default config
     */
    private function getDefaultConfig(): array
    {
        return [
            'templates_path' => __DIR__ . '/Templates',
            'cache_enabled' => true,
            'cache_ttl' => 3600,
            'validation_strict' => true,
            'output_format' => 'html5',
            'schema_version' => '13.0'
        ];
    }

    /**
     * Get library version
     *
     * @return string Version number
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Get configuration
     *
     * @return array Current configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}