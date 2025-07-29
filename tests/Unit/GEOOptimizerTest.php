<?php

namespace GEOOptimizer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use GEOOptimizer\GEOOptimizer;
use GEOOptimizer\Exceptions\GEOException;

class GEOOptimizerTest extends TestCase
{
    private $geoOptimizer;
    private $sampleBusinessData;

    protected function setUp(): void
    {
        $this->geoOptimizer = new GEOOptimizer();
        
        $this->sampleBusinessData = [
            'business_name' => 'Joe\'s Pizza',
            'description' => 'Authentic Italian pizza restaurant serving fresh, handmade pizzas since 1985.',
            'industry' => 'restaurant',
            'business_type' => 'Restaurant',
            'location' => '123 Main St, New York, NY 10001',
            'phone' => '555-123-4567',
            'email' => 'info@joespizza.com',
            'website' => 'https://joespizza.com',
            'services' => ['Dine-in', 'Takeout', 'Delivery'],
            'cuisine_type' => 'Italian',
            'hours' => [
                'Monday' => '11:00 AM - 10:00 PM',
                'Tuesday' => '11:00 AM - 10:00 PM',
                'Wednesday' => '11:00 AM - 10:00 PM',
                'Thursday' => '11:00 AM - 10:00 PM',
                'Friday' => '11:00 AM - 11:00 PM',
                'Saturday' => '11:00 AM - 11:00 PM',
                'Sunday' => '12:00 PM - 9:00 PM'
            ]
        ];
    }

    public function testConstructorWithDefaultConfig()
    {
        $optimizer = new GEOOptimizer();
        $config = $optimizer->getConfig();
        
        $this->assertIsArray($config);
        $this->assertTrue($config['cache_enabled']);
        $this->assertEquals(3600, $config['cache_ttl']);
    }

    public function testConstructorWithCustomConfig()
    {
        $customConfig = [
            'cache_enabled' => false,
            'cache_ttl' => 7200
        ];
        
        $optimizer = new GEOOptimizer($customConfig);
        $config = $optimizer->getConfig();
        
        $this->assertFalse($config['cache_enabled']);
        $this->assertEquals(7200, $config['cache_ttl']);
    }

    public function testGenerateLLMSTxt()
    {
        $result = $this->geoOptimizer->generateLLMSTxt($this->sampleBusinessData);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('Joe\'s Pizza', $result);
        $this->assertStringContainsString('Italian pizza restaurant', $result);
        $this->assertStringContainsString('555-123-4567', $result);
    }

    public function testGenerateLLMSTxtWithMissingRequiredData()
    {
        $this->expectException(\Exception::class);
        
        $incompleteData = [
            'business_name' => 'Test Business'
            // Missing required 'description' field
        ];
        
        $this->geoOptimizer->generateLLMSTxt($incompleteData);
    }

    public function testGenerateSchema()
    {
        $result = $this->geoOptimizer->generateSchema('Restaurant', $this->sampleBusinessData);
        
        $this->assertIsArray($result);
        $this->assertEquals('Restaurant', $result['@type']);
        $this->assertEquals('Joe\'s Pizza', $result['name']);
        $this->assertEquals('Italian', $result['servesCuisine']);
    }

    public function testGenerateSchemaWithUnsupportedType()
    {
        $this->expectException(\Exception::class);
        
        $this->geoOptimizer->generateSchema('UnsupportedType', $this->sampleBusinessData);
    }

    public function testAnalyzeContent()
    {
        $content = 'Joe\'s Pizza is a family-owned restaurant located in downtown New York. We serve authentic Italian pizza made with fresh ingredients. Our experienced team has been serving the community for over 35 years. Contact us at 555-123-4567 for reservations.';
        
        $result = $this->geoOptimizer->analyzeContent($content);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('geo_score', $result);
        $this->assertArrayHasKey('word_count', $result);
        $this->assertArrayHasKey('recommendations', $result);
        
        $this->assertGreaterThan(0, $result['overall_score']);
        $this->assertGreaterThan(30, $result['word_count']);
    }

    public function testAnalyzeEmptyContent()
    {
        $this->expectException(\Exception::class);
        
        $this->geoOptimizer->analyzeContent('');
    }

    public function testGetIndustryTemplate()
    {
        $template = $this->geoOptimizer->getIndustryTemplate('restaurant');
        
        $this->assertIsString($template);
        $this->assertStringContainsString('Restaurant', $template);
        $this->assertStringContainsString('{{ business_name }}', $template);
    }

    public function testGetIndustryTemplateWithFallback()
    {
        // Test with non-existent industry - should fallback to business template
        $template = $this->geoOptimizer->getIndustryTemplate('nonexistent');
        
        $this->assertIsString($template);
        $this->assertStringContainsString('{{ business_name }}', $template);
    }

    public function testOptimizeComplete()
    {
        $result = $this->geoOptimizer->optimize($this->sampleBusinessData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('llms_txt', $result);
        $this->assertArrayHasKey('schema', $result);
        $this->assertArrayHasKey('template', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('timestamp', $result);
        
        $this->assertEquals('success', $result['status']);
        
        // Verify llms.txt content
        $this->assertStringContainsString('Joe\'s Pizza', $result['llms_txt']);
        
        // Verify schema content
        $this->assertEquals('Restaurant', $result['schema']['@type']);
        
        // Verify template content
        $this->assertStringContainsString('Restaurant', $result['template']);
    }

    public function testOptimizeWithContent()
    {
        $dataWithContent = array_merge($this->sampleBusinessData, [
            'content' => 'Joe\'s Pizza serves the best Italian food in New York. Our location at 123 Main Street has been a neighborhood favorite for decades. Call us today!'
        ]);
        
        $result = $this->geoOptimizer->optimize($dataWithContent);
        
        $this->assertArrayHasKey('content_analysis', $result);
        $this->assertIsArray($result['content_analysis']);
        $this->assertArrayHasKey('overall_score', $result['content_analysis']);
    }

    public function testSetConfig()
    {
        $newConfig = [
            'cache_enabled' => false,
            'analysis' => [
                'min_word_count' => 500
            ]
        ];
        
        $this->geoOptimizer->setConfig($newConfig);
        $config = $this->geoOptimizer->getConfig();
        
        $this->assertFalse($config['cache_enabled']);
        $this->assertEquals(500, $config['analysis']['min_word_count']);
    }

    public function testGetVersion()
    {
        $version = $this->geoOptimizer->getVersion();
        
        $this->assertIsString($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);
    }

    public function testTrackCitations()
    {
        // This is a mock test since citation tracking would require external API calls
        $result = $this->geoOptimizer->trackCitations('Joe\'s Pizza');
        
        $this->assertIsArray($result);
        // Add more specific assertions based on your CitationTracker implementation
    }
}