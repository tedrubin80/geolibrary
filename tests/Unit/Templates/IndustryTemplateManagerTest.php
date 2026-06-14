<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use GEOOptimizer\Templates\IndustryTemplateManager;
use GEOOptimizer\Exceptions\ValidationException;

class IndustryTemplateManagerTest extends TestCase
{
    private IndustryTemplateManager $manager;

    protected function setUp(): void
    {
        $this->manager = new IndustryTemplateManager();
    }

    public function testGetTemplateForKnownIndustries(): void
    {
        $industries = ['restaurant', 'legal', 'medical', 'home_services',
                      'automotive', 'retail', 'real_estate', 'fitness', 'beauty', 'technology'];

        foreach ($industries as $industry) {
            $template = $this->manager->getTemplate($industry);

            $this->assertIsArray($template);
            $this->assertArrayHasKey('llms_template', $template);
            $this->assertArrayHasKey('primary_schema', $template);
            $this->assertArrayHasKey('additional_schemas', $template);
            $this->assertArrayHasKey('required_fields', $template);
            $this->assertArrayHasKey('recommended_fields', $template);
        }
    }

    public function testGetTemplateWithFallback(): void
    {
        // Test fallback to business template for unknown industry
        $template = $this->manager->getTemplate('unknown_industry');

        $this->assertIsArray($template);
        $this->assertArrayHasKey('llms_template', $template);
        $this->assertEquals('LocalBusiness', $template['primary_schema']);
    }

    public function testGenerateIndustryLLMSTxt(): void
    {
        $businessData = [
            'business_name' => 'Joe\'s Pizza',
            'cuisine_type' => 'Italian',
            'description' => 'Authentic Italian pizza',
            'location' => 'New York, NY',
            'hours' => 'Mon-Fri 11am-10pm',
            'phone' => '555-123-4567',
            'specialties' => 'Wood-fired pizza',
            'price_range' => '$$',
            'accepts_reservations' => 'Yes'
        ];

        $result = $this->manager->generateIndustryLLMSTxt($businessData, 'restaurant');

        $this->assertIsString($result);
        $this->assertStringContainsString('Joe\'s Pizza', $result);
        $this->assertStringContainsString('Italian', $result);
        $this->assertStringContainsString('555-123-4567', $result);
        $this->assertStringNotContainsString('{{ ', $result); // No unprocessed placeholders
    }

    public function testGetIndustrySchema(): void
    {
        $businessData = [
            'name' => 'Test Legal Firm',
            'practice_areas' => 'Corporate Law'
        ];

        $schema = $this->manager->getIndustrySchema($businessData, 'legal');

        $this->assertArrayHasKey('primary_schema', $schema);
        $this->assertArrayHasKey('additional_schemas', $schema);
        $this->assertArrayHasKey('required_fields', $schema);
        $this->assertArrayHasKey('recommended_fields', $schema);

        $this->assertEquals('LegalService', $schema['primary_schema']);
        $this->assertContains('LocalBusiness', $schema['additional_schemas']);
    }

    public function testGetAvailableIndustries(): void
    {
        $industries = $this->manager->getAvailableIndustries();

        $this->assertIsArray($industries);
        $this->assertNotEmpty($industries);

        // Check that all expected industries are present
        $expectedIndustries = ['restaurant', 'legal', 'medical', 'home_services', 'business'];
        foreach ($expectedIndustries as $expected) {
            $this->assertContains($expected, $industries);
        }
    }

    public function testRestaurantTemplateSpecifics(): void
    {
        $template = $this->manager->getTemplate('restaurant');

        $this->assertEquals('Restaurant', $template['primary_schema']);
        $this->assertContains('Menu', $template['additional_schemas']);
        $this->assertContains('cuisine_type', $template['required_fields']);
        $this->assertContains('accepts_reservations', $template['recommended_fields']);

        // Check FAQ suggestions are present
        $this->assertArrayHasKey('faq_suggestions', $template);
        $this->assertContains('Do you take reservations?', $template['faq_suggestions']);

        // Check authority signals
        $this->assertArrayHasKey('authority_signals', $template);
        $this->assertContains('Chef credentials', $template['authority_signals']);
    }

    public function testLegalTemplateSpecifics(): void
    {
        $template = $this->manager->getTemplate('legal');

        $this->assertEquals('LegalService', $template['primary_schema']);
        $this->assertContains('Attorney', $template['additional_schemas']);
        $this->assertContains('practice_areas', $template['required_fields']);
        $this->assertContains('bar_certifications', $template['recommended_fields']);
    }

    public function testMedicalTemplateSpecifics(): void
    {
        $template = $this->manager->getTemplate('medical');

        $this->assertEquals('MedicalBusiness', $template['primary_schema']);
        $this->assertContains('Physician', $template['additional_schemas']);
        $this->assertContains('medical_specialty', $template['required_fields']);
        $this->assertContains('insurance_accepted', $template['recommended_fields']);
    }

    public function testGetContentSuggestions(): void
    {
        $suggestions = $this->manager->getContentSuggestions('restaurant');

        $this->assertArrayHasKey('faq_suggestions', $suggestions);
        $this->assertArrayHasKey('authority_signals', $suggestions);
        $this->assertArrayHasKey('required_content', $suggestions);
        $this->assertArrayHasKey('optimization_tips', $suggestions);

        $this->assertIsArray($suggestions['faq_suggestions']);
        $this->assertNotEmpty($suggestions['faq_suggestions']);

        // Check restaurant-specific content requirements
        $this->assertContains('Menu', $suggestions['required_content']);
    }

    public function testTemplateProcessingWithMissingFields(): void
    {
        $incompleteData = [
            'business_name' => 'Test Business',
            // Missing other fields
        ];

        $result = $this->manager->generateIndustryLLMSTxt($incompleteData, 'restaurant');

        $this->assertIsString($result);
        $this->assertStringContainsString('Test Business', $result);
        // Result should be a string (even if placeholders remain due to missing data)
        $this->assertNotEmpty($result);
    }

    public function testTemplateProcessingWithArrayValues(): void
    {
        $dataWithArrays = [
            'business_name' => 'Multi-Service Business',
            'services' => ['Service 1', 'Service 2', 'Service 3']
        ];

        $result = $this->manager->generateIndustryLLMSTxt($dataWithArrays, 'business');

        $this->assertStringContainsString('Service 1, Service 2, Service 3', $result);
    }

    public function testCaseInsensitiveIndustryLookup(): void
    {
        $template1 = $this->manager->getTemplate('RESTAURANT');
        $template2 = $this->manager->getTemplate('restaurant');
        $template3 = $this->manager->getTemplate('ReStAuRaNt');

        $this->assertEquals($template1['primary_schema'], $template2['primary_schema']);
        $this->assertEquals($template2['primary_schema'], $template3['primary_schema']);
    }
}