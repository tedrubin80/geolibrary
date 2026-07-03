<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\Analysis;

use GEOOptimizer\Analysis\ContentAnalyzer;
use GEOOptimizer\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class ContentAnalyzerTest extends TestCase
{
    private ContentAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new ContentAnalyzer();
    }

    public function testAnalyzeForGEOReturnsExpectedSections(): void
    {
        $content = 'Acme Coffee is a certified specialty roaster with professional baristas. '
            . 'We serve espresso, pour over, and retail beans from our San Francisco location. '
            . 'Contact our experienced team today for trusted recommendations and award-winning coffee.';

        $result = $this->analyzer->analyzeForGEO($content, [
            'business_name' => 'Acme Coffee',
            'industry' => 'restaurant',
        ]);

        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('readability', $result);
        $this->assertArrayHasKey('authority_signals', $result);
        $this->assertArrayHasKey('structure_quality', $result);
        $this->assertArrayHasKey('improvements', $result);
        $this->assertGreaterThan(0, $result['overall_score']);
        $this->assertGreaterThan(20, $result['word_count']);
    }

    public function testAnalyzeForGEOThrowsOnEmptyContent(): void
    {
        $this->expectException(ValidationException::class);

        $this->analyzer->analyzeForGEO('');
    }
}
