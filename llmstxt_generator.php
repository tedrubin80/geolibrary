<?php

namespace GEOOptimizer\LLMSTxt;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use GEOOptimizer\Exceptions\ValidationException;

/**
 * LLMSTxt Generator
 * 
 * Generates llms.txt files optimized for AI search engines
 */
class Generator
{
    private $twig;
    private $templatesPath;

    public function __construct(string $templatesPath = null)
    {
        $this->templatesPath = $templatesPath ?: __DIR__ . '/Templates';
        $this->initializeTwig();
    }

    /**
     * Generate llms.txt content
     */
    public function generate(array $businessData): string
    {
        $this->validateBusinessData($businessData);

        $industry = $businessData['industry'] ?? 'business';
        $templateFile = $this->getTemplateFile($industry);

        // Prepare template variables
        $variables = $this->prepareTemplateVariables($businessData);

        try {
            return $this->twig->render($templateFile, $variables);
        } catch (\Exception $e) {
            throw new ValidationException('Failed to generate llms.txt: ' . $e->getMessage());
        }
    }

    /**
     * Get available industry templates
     */
    public function getAvailableTemplates(): array
    {
        $templates = [];
        $files = glob($this->templatesPath . '/*.txt');
        
        foreach ($files as $file) {
            $templates[] = basename($file, '.txt');
        }

        return $templates;
    }

    /**
     * Validate business data
     */
    private function validateBusinessData(array $data): void
    {
        $required = ['business_name', 'description'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new ValidationException("Required field '{$field}' is missing or empty");
            }
        }
    }

    /**
     * Get template file for industry
     */
    private function getTemplateFile(string $industry): string
    {
        $templateFile = $industry . '.txt';
        $templatePath = $this->templatesPath . '/' . $templateFile;

        if (!file_exists($templatePath)) {
            // Fallback to business template
            $templateFile = 'business.txt';
            $templatePath = $this->templatesPath . '/' . $templateFile;

            if (!file_exists($templatePath)) {
                throw new ValidationException("Template file not found: {$templateFile}");
            }
        }

        return $templateFile;
    }

    /**
     * Prepare variables for template rendering
     */
    private function prepareTemplateVariables(array $businessData): array
    {
        // Set defaults and format data
        $variables = array_merge([
            'business_name' => '',
            'description' => '',
            'services' => [],
            'location' => '',
            'phone' => '',
            'email' => '',
            'website' => '',
            'hours' => [],
            'specialties' => [],
            'certifications' => [],
            'years_in_business' => '',
            'team_size' => '',
            'service_area' => '',
            'emergency_services' => false,
            'languages' => ['English'],
            'payment_methods' => [],
            'insurance_accepted' => [],
            'awards' => [],
            'current_date' => date('Y-m-d'),
            'current_year' => date('Y')
        ], $businessData);

        // Format arrays as comma-separated strings for templates
        $arrayFields = ['services', 'specialties', 'certifications', 'languages', 'payment_methods', 'insurance_accepted', 'awards'];
        foreach ($arrayFields as $field) {
            if (is_array($variables[$field])) {
                $variables[$field . '_list'] = implode(', ', $variables[$field]);
            }
        }

        return $variables;
    }

    /**
     * Initialize Twig environment
     */
    private function initializeTwig(): void
    {
        $loader = new FilesystemLoader($this->templatesPath);
        $this->twig = new Environment($loader, [
            'cache' => false, // Disable cache for development
            'debug' => false,
            'strict_variables' => false
        ]);

        // Add custom filters
        $this->addCustomFilters();
    }

    /**
     * Add custom Twig filters
     */
    private function addCustomFilters(): void
    {
        // Add a filter to format phone numbers
        $phoneFilter = new \Twig\TwigFilter('phone', function ($phone) {
            // Basic phone formatting - can be enhanced
            return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', preg_replace('/\D/', '', $phone));
        });
        $this->twig->addFilter($phoneFilter);

        // Add a filter to format addresses
        $addressFilter = new \Twig\TwigFilter('address', function ($address) {
            return trim(preg_replace('/\s+/', ' ', $address));
        });
        $this->twig->addFilter($addressFilter);
    }
}