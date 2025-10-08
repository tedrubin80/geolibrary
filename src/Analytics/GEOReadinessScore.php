<?php

declare(strict_types=1);

namespace GEOOptimizer\Analytics;

use GEOOptimizer\Analysis\ContentAnalyzer;
use GEOOptimizer\StructuredData\SchemaGenerator;

/**
 * GEO Readiness Score Calculator
 *
 * Evaluates content and structure to determine how well-optimized
 * it is for AI search engines and generates actionable recommendations
 */
class GEOReadinessScore
{
    private ContentAnalyzer $contentAnalyzer;
    private SchemaGenerator $schemaGenerator;

    /**
     * Score weights for different factors
     * @var array<string, float>
     */
    private array $weights = [
        'content_quality' => 0.25,
        'structured_data' => 0.20,
        'authority_signals' => 0.15,
        'technical_optimization' => 0.15,
        'freshness' => 0.10,
        'comprehensiveness' => 0.10,
        'citations' => 0.05
    ];

    public function __construct()
    {
        $this->contentAnalyzer = new ContentAnalyzer();
        $this->schemaGenerator = new SchemaGenerator();
    }

    /**
     * Calculate comprehensive GEO readiness score
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function calculate(array $data): array
    {
        $scores = [
            'content_quality' => $this->assessContentQuality($data),
            'structured_data' => $this->assessStructuredData($data),
            'authority_signals' => $this->assessAuthoritySignals($data),
            'technical_optimization' => $this->assessTechnicalOptimization($data),
            'freshness' => $this->assessFreshness($data),
            'comprehensiveness' => $this->assessComprehensiveness($data),
            'citations' => $this->assessCitations($data)
        ];

        $overallScore = $this->calculateWeightedScore($scores);

        return [
            'overall_score' => $overallScore,
            'grade' => $this->getGrade($overallScore),
            'scores' => $scores,
            'recommendations' => $this->generateRecommendations($scores),
            'strengths' => $this->identifyStrengths($scores),
            'weaknesses' => $this->identifyWeaknesses($scores),
            'ai_readiness_level' => $this->getReadinessLevel($overallScore),
            'estimated_visibility' => $this->estimateVisibility($overallScore),
            'timestamp' => date('c')
        ];
    }

    /**
     * Assess content quality for AI consumption
     *
     * @param array<string, mixed> $data
     */
    private function assessContentQuality(array $data): float
    {
        $score = 0.0;
        $factors = 0;

        // Check for llms.txt presence
        if (!empty($data['has_llms_txt'])) {
            $score += 20;
            $factors++;
        }

        // Analyze content if provided
        if (!empty($data['content'])) {
            $analysis = $this->contentAnalyzer->analyzeForGEO($data['content']);

            // Readability score
            if ($analysis['readability_score'] >= 60) {
                $score += min(30, $analysis['readability_score'] - 30);
                $factors++;
            }

            // Authority keywords
            if ($analysis['authority_score'] >= 5) {
                $score += min(25, $analysis['authority_score'] * 2);
                $factors++;
            }

            // Content length
            $wordCount = str_word_count($data['content']);
            if ($wordCount >= 500) {
                $score += min(25, ($wordCount / 100) * 2);
                $factors++;
            }
        }

        return $factors > 0 ? min(100, $score / $factors * 4) : 0;
    }

    /**
     * Assess structured data implementation
     *
     * @param array<string, mixed> $data
     */
    private function assessStructuredData(array $data): float
    {
        $score = 0.0;

        // Check for schema markup
        if (!empty($data['has_schema'])) {
            $score += 40;

            // Bonus for multiple schema types
            if (!empty($data['schema_types'])) {
                $score += min(30, count($data['schema_types']) * 10);
            }

            // Bonus for comprehensive schema
            if (!empty($data['schema_properties'])) {
                $score += min(30, count($data['schema_properties']) * 2);
            }
        }

        return min(100, $score);
    }

    /**
     * Assess authority signals
     *
     * @param array<string, mixed> $data
     */
    private function assessAuthoritySignals(array $data): float
    {
        $score = 0.0;

        // Business verification
        if (!empty($data['verified_business'])) {
            $score += 25;
        }

        // Reviews and ratings
        if (!empty($data['has_reviews'])) {
            $score += 15;
            if (($data['average_rating'] ?? 0) >= 4.0) {
                $score += 10;
            }
        }

        // Citations from authoritative sources
        if (!empty($data['external_citations'])) {
            $score += min(25, count($data['external_citations']) * 5);
        }

        // Industry certifications
        if (!empty($data['certifications'])) {
            $score += min(25, count($data['certifications']) * 10);
        }

        return min(100, $score);
    }

    /**
     * Assess technical optimization
     *
     * @param array<string, mixed> $data
     */
    private function assessTechnicalOptimization(array $data): float
    {
        $score = 0.0;

        // Mobile-friendly
        if (!empty($data['mobile_friendly'])) {
            $score += 25;
        }

        // Page speed
        if (!empty($data['page_speed_score'])) {
            $score += min(25, $data['page_speed_score'] / 4);
        }

        // HTTPS
        if (!empty($data['https'])) {
            $score += 20;
        }

        // Sitemap
        if (!empty($data['has_sitemap'])) {
            $score += 15;
        }

        // Robots.txt
        if (!empty($data['has_robots_txt'])) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * Assess content freshness
     *
     * @param array<string, mixed> $data
     */
    private function assessFreshness(array $data): float
    {
        $score = 0.0;

        if (!empty($data['last_updated'])) {
            $lastUpdated = strtotime($data['last_updated']);
            $daysSinceUpdate = (time() - $lastUpdated) / 86400;

            if ($daysSinceUpdate <= 7) {
                $score = 100;
            } elseif ($daysSinceUpdate <= 30) {
                $score = 80;
            } elseif ($daysSinceUpdate <= 90) {
                $score = 60;
            } elseif ($daysSinceUpdate <= 180) {
                $score = 40;
            } elseif ($daysSinceUpdate <= 365) {
                $score = 20;
            }
        }

        // Regular update frequency bonus
        if (!empty($data['update_frequency']) && $data['update_frequency'] === 'regular') {
            $score = min(100, $score + 20);
        }

        return $score;
    }

    /**
     * Assess content comprehensiveness
     *
     * @param array<string, mixed> $data
     */
    private function assessComprehensiveness(array $data): float
    {
        $score = 0.0;

        // FAQ section
        if (!empty($data['has_faq'])) {
            $score += 20;
        }

        // How-to content
        if (!empty($data['has_howto'])) {
            $score += 20;
        }

        // Multiple content formats
        if (!empty($data['content_formats'])) {
            $score += min(30, count($data['content_formats']) * 10);
        }

        // Comprehensive service/product descriptions
        if (!empty($data['detailed_descriptions'])) {
            $score += 30;
        }

        return min(100, $score);
    }

    /**
     * Assess citation potential
     *
     * @param array<string, mixed> $data
     */
    private function assessCitations(array $data): float
    {
        $score = 0.0;

        // Unique value proposition
        if (!empty($data['unique_content'])) {
            $score += 30;
        }

        // Statistical data
        if (!empty($data['has_statistics'])) {
            $score += 25;
        }

        // Original research
        if (!empty($data['original_research'])) {
            $score += 25;
        }

        // Expert quotes
        if (!empty($data['expert_quotes'])) {
            $score += 20;
        }

        return min(100, $score);
    }

    /**
     * Calculate weighted overall score
     *
     * @param array<string, float> $scores
     */
    private function calculateWeightedScore(array $scores): float
    {
        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($scores as $category => $score) {
            if (isset($this->weights[$category])) {
                $weightedSum += $score * $this->weights[$category];
                $totalWeight += $this->weights[$category];
            }
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : 0;
    }

    /**
     * Get letter grade based on score
     */
    private function getGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 77) return 'B+';
        if ($score >= 73) return 'B';
        if ($score >= 70) return 'B-';
        if ($score >= 67) return 'C+';
        if ($score >= 63) return 'C';
        if ($score >= 60) return 'C-';
        if ($score >= 57) return 'D+';
        if ($score >= 53) return 'D';
        if ($score >= 50) return 'D-';
        return 'F';
    }

    /**
     * Get AI readiness level
     */
    private function getReadinessLevel(float $score): string
    {
        if ($score >= 80) return 'Excellent - Highly optimized for AI discovery';
        if ($score >= 60) return 'Good - Well-positioned for AI citations';
        if ($score >= 40) return 'Fair - Some optimization needed';
        if ($score >= 20) return 'Poor - Significant improvements required';
        return 'Critical - Urgent optimization needed';
    }

    /**
     * Estimate visibility in AI results
     */
    private function estimateVisibility(float $score): string
    {
        if ($score >= 80) return 'High - Likely to appear in top AI responses';
        if ($score >= 60) return 'Moderate - May appear in detailed AI responses';
        if ($score >= 40) return 'Low - Occasionally cited by AI';
        if ($score >= 20) return 'Minimal - Rarely referenced by AI';
        return 'Very Low - Unlikely to be discovered by AI';
    }

    /**
     * Generate actionable recommendations
     *
     * @param array<string, float> $scores
     * @return array<string>
     */
    private function generateRecommendations(array $scores): array
    {
        $recommendations = [];

        // Content quality recommendations
        if ($scores['content_quality'] < 70) {
            $recommendations[] = 'Create an llms.txt file to explicitly communicate with AI systems';
            $recommendations[] = 'Improve content readability and structure for AI parsing';
            $recommendations[] = 'Add more authoritative keywords and industry terminology';
        }

        // Structured data recommendations
        if ($scores['structured_data'] < 70) {
            $recommendations[] = 'Implement comprehensive Schema.org markup';
            $recommendations[] = 'Add FAQ schema for common questions';
            $recommendations[] = 'Include Product or Service schema with detailed properties';
        }

        // Authority recommendations
        if ($scores['authority_signals'] < 70) {
            $recommendations[] = 'Collect and display customer reviews';
            $recommendations[] = 'Obtain industry certifications and accreditations';
            $recommendations[] = 'Build citations from authoritative sources';
        }

        // Technical recommendations
        if ($scores['technical_optimization'] < 70) {
            $recommendations[] = 'Improve page load speed for better crawling';
            $recommendations[] = 'Ensure mobile-friendly design';
            $recommendations[] = 'Implement HTTPS if not already done';
        }

        // Freshness recommendations
        if ($scores['freshness'] < 70) {
            $recommendations[] = 'Update content more frequently';
            $recommendations[] = 'Add last-modified dates to content';
            $recommendations[] = 'Implement a regular content update schedule';
        }

        return array_slice($recommendations, 0, 5); // Top 5 recommendations
    }

    /**
     * Identify strengths
     *
     * @param array<string, float> $scores
     * @return array<string>
     */
    private function identifyStrengths(array $scores): array
    {
        $strengths = [];

        foreach ($scores as $category => $score) {
            if ($score >= 70) {
                $strengths[] = ucfirst(str_replace('_', ' ', $category));
            }
        }

        return $strengths;
    }

    /**
     * Identify weaknesses
     *
     * @param array<string, float> $scores
     * @return array<string>
     */
    private function identifyWeaknesses(array $scores): array
    {
        $weaknesses = [];

        foreach ($scores as $category => $score) {
            if ($score < 50) {
                $weaknesses[] = ucfirst(str_replace('_', ' ', $category));
            }
        }

        return $weaknesses;
    }
}