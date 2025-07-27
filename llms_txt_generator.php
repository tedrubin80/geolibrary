<?php

namespace GEOOptimizer\LLMSTxt;

use GEOOptimizer\Exceptions\ValidationException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * LLMs.txt File Generator
 * 
 * Generates llms.txt files optimized for AI language models
 * following the emerging llms.txt standard.
 */
class Generator
{
    private $config;
    private $twig;
    private $templates;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->initializeTwig();
        $this->loadTemplates();
    }

    /**
     * Generate llms.txt content
     *
     * @param array $businessData Business information
     * @param string $template Template name (business, ecommerce, service)
     * @return string Generated llms.txt content
     */
    public function generate(array $businessData, string $template = 'business'): string
    {
        $this->validateBusinessData($businessData);
        
        $templateData = $this->prepareTemplateData($businessData);
        
        return $this->twig->render("{$template}.txt", $templateData);
    }

    /**
     * Save llms.txt file to specified path
     *
     * @param array $businessData Business information
     * @param string $path File path
     * @param string $template Template name
     * @return bool Success status
     */
    public function save(array $businessData, string $path = 'public/llms.txt', string $template = 'business'): bool
    {
        $content = $this->generate($businessData, $template);
        
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        return file_put_contents($path, $content) !== false;
    }

    /**
     * Get available templates
     *
     * @return array List of available templates
     */
    public function getAvailableTemplates(): array
    {
        return array_keys($this->templates);
    }

    /**
     * Validate required business data
     *
     * @param array $businessData
     * @throws ValidationException
     */
    private function validateBusinessData(array $businessData): void
    {
        $required = [
            'name' => 'Business name is required',
            'description' => 'Business description is required',
            'industry' => 'Industry classification is required'
        ];

        foreach ($required as $field => $message) {
            if (!isset($businessData[$field]) || empty(trim($businessData[$field]))) {
                throw new ValidationException($message);
            }
        }
    }

    /**
     * Prepare data for template rendering
     *
     * @param array $businessData
     * @return array Prepared template data
     */
    private function prepareTemplateData(array $businessData): array
    {
        return [
            // Basic Information
            'business_name' => $businessData['name'],
            'description' => $businessData['description'],
            'industry' => $businessData['industry'],
            'founded' => $businessData['founded'] ?? '',
            'location' => $businessData['location'] ?? '',
            
            // Services and Expertise
            'services' => $this->formatList($businessData['services'] ?? []),
            'specialties' => $this->formatList($businessData['specialties'] ?? []),
            'target_market' => $businessData['target_market'] ?? '',
            'service_area' => $businessData['service_area'] ?? '',
            
            // Authority Signals
            'certifications' => $this->formatList($businessData['certifications'] ?? []),
            'awards' => $this->formatList($businessData['awards'] ?? []),
            'years_experience' => $businessData['years_experience'] ?? '',
            'team_size' => $businessData['team_size'] ?? '',
            
            // Contact Information
            'website' => $businessData['website'] ?? '',
            'phone' => $businessData['phone'] ?? '',
            'email' => $businessData['email'] ?? '',
            'address' => $businessData['address'] ?? '',
            
            // Content Guidelines
            'brand_voice' => $businessData['brand_voice'] ?? 'Professional and helpful',
            'key_messages' => $this->formatList($businessData['key_messages'] ?? []),
            'avoid_topics' => $this->formatList($businessData['avoid_topics'] ?? []),
            'language_style' => $businessData['language_style'] ?? 'Clear and accessible',
            
            // Meta
            'generated_date' => date('Y-m-d'),
            'last_updated' => $businessData['last_updated'] ?? date('Y-m-d')
        ];
    }

    /**
     * Format array as comma-separated list
     *
     * @param array $items
     * @return string Formatted list
     */
    private function formatList(array $items): string
    {
        return implode(', ', array_filter($items));
    }

    /**
     * Initialize Twig templating engine
     */
    private function initializeTwig(): void
    {
        $templatePath = $this->config['templates_path'] ?? __DIR__ . '/Templates';
        $loader = new FilesystemLoader($templatePath);
        $this->twig = new Environment($loader, [
            'cache' => $this->config['cache_enabled'] ?? false,
            'auto_reload' => true
        ]);
    }

    /**
     * Load available templates
     */
    private function loadTemplates(): void
    {
        $this->templates = [
            'business' => 'General business template',
            'ecommerce' => 'E-commerce business template',
            'service' => 'Service-based business template',
            'professional' => 'Professional services template',
            'local' => 'Local business template'
        ];
    }

    /**
     * Create custom template
     *
     * @param string $name Template name
     * @param string $content Template content
     * @return bool Success status
     */
    public function createCustomTemplate(string $name, string $content): bool
    {
        $templatePath = $this->config['templates_path'] ?? __DIR__ . '/Templates';
        $filePath = $templatePath . '/' . $name . '.txt';
        
        if (!is_dir($templatePath)) {
            mkdir($templatePath, 0755, true);
        }
        
        $success = file_put_contents($filePath, $content) !== false;
        
        if ($success) {
            $this->templates[$name] = 'Custom template';
        }
        
        return $success;
    }
}