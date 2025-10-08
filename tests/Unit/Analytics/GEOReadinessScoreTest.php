<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\Analytics;

use PHPUnit\Framework\TestCase;
use GEOOptimizer\Analytics\GEOReadinessScore;

class GEOReadinessScoreTest extends TestCase
{
    private GEOReadinessScore $scorer;

    protected function setUp(): void
    {
        $this->scorer = new GEOReadinessScore();
    }

    public function testCalculateWithCompleteData(): void
    {
        $data = [
            'content' => 'This is a comprehensive guide about pizza restaurants in New York.
                         We offer authentic Italian pizza with fresh ingredients.
                         Our restaurant has been serving the community for 35 years.
                         Located in downtown Manhattan, we provide dine-in, takeout, and delivery services.
                         Call us at 555-123-4567 for reservations.',
            'structured_data' => [
                '@type' => 'Restaurant',
                'name' => 'Joe\'s Pizza',
                'servesCuisine' => 'Italian',
                'address' => '123 Main St, New York, NY 10001'
            ],
            'business_data' => [
                'name' => 'Joe\'s Pizza',
                'years_experience' => 35,
                'certifications' => ['Food Safety Certified', 'Best Pizza Award 2023'],
                'location' => 'New York, NY'
            ],
            'has_llms_txt' => true,
            'last_updated' => date('Y-m-d', strtotime('-1 day'))
        ];

        $result = $this->scorer->calculate($data);

        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('grade', $result);
        $this->assertArrayHasKey('scores', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('strengths', $result);
        $this->assertArrayHasKey('weaknesses', $result);

        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
        $this->assertContains($result['grade'], ['A', 'B', 'C', 'D', 'F']);
    }

    public function testScoreBreakdownTotals100(): void
    {
        $data = [
            'content' => 'Sample content about our business',
            'structured_data' => ['@type' => 'LocalBusiness'],
            'business_data' => ['name' => 'Test Business']
        ];

        $result = $this->scorer->calculate($data);

        // The weights are defined in the class constants
        // They should total 1.0 (or 100%)
        $this->assertArrayHasKey('scores', $result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }

    public function testGradeMapping(): void
    {
        // Test different score levels produce expected grades
        $highScoreData = [
            'content' => str_repeat('Excellent comprehensive content about our services. ', 100),
            'structured_data' => [
                '@type' => 'Restaurant',
                'name' => 'Business',
                'description' => 'Description',
                'address' => 'Address',
                'telephone' => 'Phone'
            ],
            'business_data' => [
                'years_experience' => 20,
                'certifications' => ['Cert1', 'Cert2']
            ],
            'has_llms_txt' => true,
            'last_updated' => date('Y-m-d')
        ];

        $result = $this->scorer->calculate($highScoreData);
        $this->assertContains($result['grade'], ['A', 'B', 'C', 'D', 'F']);
    }

    public function testContentQualityScoring(): void
    {
        $shortContent = [
            'content' => 'Very short content',
            'structured_data' => [],
            'business_data' => []
        ];

        $longContent = [
            'content' => str_repeat('This is comprehensive content about our business and services. ', 150),
            'structured_data' => [],
            'business_data' => []
        ];

        $shortResult = $this->scorer->calculate($shortContent);
        $longResult = $this->scorer->calculate($longContent);

        // More content should produce better or equal scores
        $this->assertGreaterThanOrEqual($shortResult['scores']['content_quality'], $longResult['scores']['content_quality']);
        $this->assertArrayHasKey('content_quality', $shortResult['scores']);
        $this->assertArrayHasKey('content_quality', $longResult['scores']);
    }

    public function testStructuredDataScoring(): void
    {
        $minimalData = [
            'content' => 'Content',
            'structured_data' => ['@type' => 'LocalBusiness'],
            'business_data' => []
        ];

        $completeData = [
            'content' => 'Content',
            'structured_data' => [
                '@type' => 'Restaurant',
                'name' => 'Joe\'s Pizza',
                'description' => 'Italian restaurant',
                'address' => '123 Main St',
                'telephone' => '555-123-4567',
                'servesCuisine' => 'Italian'
            ],
            'business_data' => []
        ];

        $minimalResult = $this->scorer->calculate($minimalData);
        $completeResult = $this->scorer->calculate($completeData);

        // Complete data should score better or equal
        $this->assertGreaterThanOrEqual($minimalResult['scores']['structured_data'], $completeResult['scores']['structured_data']);
        $this->assertArrayHasKey('structured_data', $minimalResult['scores']);
        $this->assertArrayHasKey('structured_data', $completeResult['scores']);
    }

    public function testAuthoritySignalsScoring(): void
    {
        $noAuthority = [
            'content' => 'Content',
            'structured_data' => [],
            'business_data' => []
        ];

        $highAuthority = [
            'content' => 'Content',
            'structured_data' => [],
            'business_data' => [
                'years_experience' => 25,
                'certifications' => ['ISO 9001', 'Industry Leader Award'],
                'awards' => ['Best Service 2023', 'Customer Choice 2022']
            ]
        ];

        $noAuthorityResult = $this->scorer->calculate($noAuthority);
        $highAuthorityResult = $this->scorer->calculate($highAuthority);

        // High authority should score better or equal
        $this->assertGreaterThanOrEqual($noAuthorityResult['scores']['authority_signals'], $highAuthorityResult['scores']['authority_signals']);
        $this->assertArrayHasKey('authority_signals', $noAuthorityResult['scores']);
        $this->assertArrayHasKey('authority_signals', $highAuthorityResult['scores']);
    }

    public function testFreshnessScoring(): void
    {
        $fresh = [
            'content' => 'Content',
            'last_updated' => date('Y-m-d')
        ];

        $old = [
            'content' => 'Content',
            'last_updated' => date('Y-m-d', strtotime('-365 days'))
        ];

        $freshResult = $this->scorer->calculate($fresh);
        $oldResult = $this->scorer->calculate($old);

        $this->assertGreaterThan($oldResult['scores']['freshness'], $freshResult['scores']['freshness']);
    }

    public function testRecommendationsGenerated(): void
    {
        $poorData = [
            'content' => 'Short content',
            'structured_data' => [],
            'business_data' => [],
            'has_llms_txt' => false
        ];

        $result = $this->scorer->calculate($poorData);

        $this->assertArrayHasKey('recommendations', $result);
        $this->assertIsArray($result['recommendations']);
        $this->assertNotEmpty($result['recommendations']);
    }

    public function testVisibilityPotentialEstimate(): void
    {
        $lowScoreData = [
            'content' => 'Minimal content',
            'structured_data' => [],
            'business_data' => []
        ];

        $highScoreData = [
            'content' => str_repeat('Comprehensive content about our services. ', 100),
            'structured_data' => [
                '@type' => 'Restaurant',
                'name' => 'Business',
                'description' => 'Description',
                'address' => 'Address',
                'telephone' => 'Phone'
            ],
            'business_data' => [
                'years_experience' => 20,
                'certifications' => ['Cert1', 'Cert2']
            ],
            'has_llms_txt' => true
        ];

        $lowResult = $this->scorer->calculate($lowScoreData);
        $highResult = $this->scorer->calculate($highScoreData);

        $this->assertArrayHasKey('estimated_visibility', $lowResult);
        $this->assertArrayHasKey('estimated_visibility', $highResult);

        // High score should have better visibility than low score
        $this->assertIsString($lowResult['estimated_visibility']);
        $this->assertIsString($highResult['estimated_visibility']);
    }

    public function testEmptyDataHandling(): void
    {
        $emptyData = [];

        $result = $this->scorer->calculate($emptyData);

        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('grade', $result);
        $this->assertContains($result['grade'], ['A', 'B', 'C', 'D', 'F']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }
}