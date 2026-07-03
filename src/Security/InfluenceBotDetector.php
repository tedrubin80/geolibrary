<?php

declare(strict_types=1);

namespace GEOOptimizer\Security;

use GEOOptimizer\Analysis\ContentAnalyzer;
use GEOOptimizer\Analytics\GEOReadinessScore;

/**
 * Influence Bot and Manipulation Detection System
 *
 * Detects coordinated influence campaigns, fake reviews, astroturfing,
 * and bot-generated content using GEO content quality analysis
 */
class InfluenceBotDetector
{
    private ContentAnalyzer $contentAnalyzer;
    private GEOReadinessScore $scorer;

    /**
     * Influence campaign patterns
     * @var array<string, array<string, mixed>>
     */
    private array $influencePatterns = [
        'coordinated_posting' => [
            'weight' => 0.25,
            'description' => 'Multiple accounts posting similar content simultaneously'
        ],
        'synthetic_content' => [
            'weight' => 0.20,
            'description' => 'AI-generated or template-based content'
        ],
        'authority_manipulation' => [
            'weight' => 0.20,
            'description' => 'Fake credentials or artificial authority signals'
        ],
        'sentiment_anomaly' => [
            'weight' => 0.15,
            'description' => 'Unnatural sentiment patterns or emotional manipulation'
        ],
        'network_clustering' => [
            'weight' => 0.12,
            'description' => 'Suspicious account relationships and interactions'
        ],
        'temporal_pattern' => [
            'weight' => 0.08,
            'description' => 'Automated posting schedules'
        ]
    ];

    public function __construct()
    {
        $this->contentAnalyzer = new ContentAnalyzer();
        $this->scorer = new GEOReadinessScore();
    }

    /**
     * Analyze content for influence bot patterns
     *
     * @param array<string, mixed> $contentData
     * @return array<string, mixed>
     */
    public function analyzeContent(array $contentData): array
    {
        $scores = [
            'coordinated_posting' => $this->detectCoordinatedPosting($contentData),
            'synthetic_content' => $this->detectSyntheticContent($contentData),
            'authority_manipulation' => $this->detectAuthorityManipulation($contentData),
            'sentiment_anomaly' => $this->detectSentimentAnomaly($contentData),
            'network_clustering' => $this->detectNetworkClustering($contentData),
            'temporal_pattern' => $this->detectTemporalPattern($contentData)
        ];

        $influenceScore = $this->calculateInfluenceScore($scores);
        $classification = $this->classifyInfluenceCampaign($influenceScore, $scores);

        return [
            'influence_score' => $influenceScore,
            'classification' => $classification,
            'threat_level' => $this->getThreatLevel($influenceScore),
            'pattern_scores' => $scores,
            'authenticity_confidence' => 100 - $influenceScore,
            'recommendations' => $this->generateRecommendations($classification, $scores),
            'should_flag' => $influenceScore >= 60,
            'should_remove' => $influenceScore >= 80,
            'timestamp' => date('c')
        ];
    }

    /**
     * Detect coordinated posting patterns
     */
    private function detectCoordinatedPosting(array $data): float
    {
        if (empty($data['related_posts'])) {
            return 0.0;
        }

        $posts = $data['related_posts'];
        $coordinationScore = 0.0;

        // Check content similarity across accounts
        if (isset($data['content_similarity_score'])) {
            if ($data['content_similarity_score'] > 0.8) {
                $coordinationScore += 40; // Very similar content = likely coordinated
            } elseif ($data['content_similarity_score'] > 0.6) {
                $coordinationScore += 20;
            }
        }

        // Check temporal clustering (posts within short time window)
        if (isset($data['temporal_clustering_score'])) {
            if ($data['temporal_clustering_score'] > 0.7) {
                $coordinationScore += 30; // Posts at same time = coordinated
            }
        }

        // Check for shared linguistic patterns
        if (isset($data['linguistic_fingerprint_match'])) {
            if ($data['linguistic_fingerprint_match'] > 0.75) {
                $coordinationScore += 30; // Same writing style = same author
            }
        }

        return min(100, $coordinationScore);
    }

    /**
     * Detect AI-generated or synthetic content
     */
    private function detectSyntheticContent(array $data): float
    {
        if (empty($data['content'])) {
            return 0.0;
        }

        $content = $data['content'];
        $syntheticScore = 0.0;

        // Analyze with ContentAnalyzer
        $analysis = $this->contentAnalyzer->analyzeForGEO($content);
        $geoReadiness = $this->scorer->calculate(['content' => $content]);

        // Check for AI generation markers
        $aiMarkers = [
            'perfect_grammar' => $this->checkPerfectGrammar($content),
            'repetitive_structure' => $this->checkRepetitiveStructure($content),
            'generic_phrasing' => $this->checkGenericPhrasing($content),
            'lack_of_personality' => $this->checkPersonality($content),
            'unnatural_transitions' => $this->checkTransitions($content)
        ];

        foreach ($aiMarkers as $marker => $detected) {
            if ($detected) {
                $syntheticScore += 20;
            }
        }

        // Check against GEO readiness - synthetic content often scores too perfectly
        if ($geoReadiness['overall_score'] > 95) {
            $syntheticScore += 10; // Suspiciously perfect GEO optimization
        }

        if (($analysis['overall_score'] ?? 0) < 20) {
            $syntheticScore += 5;
        }

        // Check for template patterns
        if (isset($data['template_match_probability']) && $data['template_match_probability'] > 0.7) {
            $syntheticScore += 20;
        }

        return min(100, $syntheticScore);
    }

    /**
     * Detect authority signal manipulation (fake credentials, reviews)
     */
    private function detectAuthorityManipulation(array $data): float
    {
        if (empty($data['authority_claims'])) {
            return 0.0;
        }

        $manipulationScore = 0.0;

        // Check for unverifiable credentials
        if (!empty($data['credentials']) && empty($data['credential_verification'])) {
            $manipulationScore += 25;
        }

        // Check for fake review patterns
        if (!empty($data['reviews'])) {
            $reviews = $data['reviews'];

            // Burst of reviews in short time
            if (isset($reviews['burst_pattern']) && $reviews['burst_pattern'] === true) {
                $manipulationScore += 20;
            }

            // Overly positive sentiment (fake reviews are often 5-star only)
            if (isset($reviews['average_rating']) && $reviews['average_rating'] > 4.9) {
                $manipulationScore += 15;
            }

            // Generic review text
            if (isset($reviews['generic_content_ratio']) && $reviews['generic_content_ratio'] > 0.7) {
                $manipulationScore += 20;
            }

            // Reviewer account age anomalies
            if (isset($reviews['new_account_ratio']) && $reviews['new_account_ratio'] > 0.6) {
                $manipulationScore += 20;
            }
        }

        // Check for citation manipulation
        if (!empty($data['citations'])) {
            if (isset($data['self_citation_ratio']) && $data['self_citation_ratio'] > 0.5) {
                $manipulationScore += 15;
            }
        }

        return min(100, $manipulationScore);
    }

    /**
     * Detect sentiment anomalies and emotional manipulation
     */
    private function detectSentimentAnomaly(array $data): float
    {
        if (empty($data['content'])) {
            return 0.0;
        }

        $anomalyScore = 0.0;

        // Check for extreme sentiment (manipulation campaigns often use strong emotions)
        if (isset($data['sentiment_intensity']) && $data['sentiment_intensity'] > 0.9) {
            $anomalyScore += 30;
        }

        // Check for emotional manipulation keywords
        $manipulationKeywords = [
            'urgent', 'crisis', 'emergency', 'shocking', 'unbelievable',
            'must act now', 'everyone is saying', 'they don\'t want you to know'
        ];

        $content = strtolower($data['content']);
        $keywordCount = 0;
        foreach ($manipulationKeywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $keywordCount++;
            }
        }

        if ($keywordCount > 0) {
            $anomalyScore += min(40, $keywordCount * 10);
        }

        // Check for sentiment inconsistency with topic
        if (isset($data['sentiment_topic_mismatch']) && $data['sentiment_topic_mismatch'] === true) {
            $anomalyScore += 30;
        }

        return min(100, $anomalyScore);
    }

    /**
     * Detect suspicious network clustering
     */
    private function detectNetworkClustering(array $data): float
    {
        if (empty($data['network_data'])) {
            return 0.0;
        }

        $network = $data['network_data'];
        $clusteringScore = 0.0;

        // Check for tight network clustering (bots often interact only with each other)
        if (isset($network['clustering_coefficient']) && $network['clustering_coefficient'] > 0.8) {
            $clusteringScore += 35;
        }

        // Check for low network diversity
        if (isset($network['interaction_diversity']) && $network['interaction_diversity'] < 0.3) {
            $clusteringScore += 25;
        }

        // Check for automated following patterns
        if (isset($network['follow_ratio']) && $network['follow_ratio'] > 0.9) {
            $clusteringScore += 20;
        }

        // Check for shared IP addresses or fingerprints
        if (isset($network['shared_fingerprints']) && $network['shared_fingerprints'] > 3) {
            $clusteringScore += 20;
        }

        return min(100, $clusteringScore);
    }

    /**
     * Detect automated temporal patterns
     */
    private function detectTemporalPattern(array $data): float
    {
        if (empty($data['posting_times'])) {
            return 0.0;
        }

        $times = $data['posting_times'];
        $patternScore = 0.0;

        // Check for too-regular posting intervals (bots post on schedule)
        if (isset($data['posting_regularity_score']) && $data['posting_regularity_score'] > 0.8) {
            $patternScore += 40;
        }

        // Check for 24/7 posting (humans sleep)
        if (isset($data['posts_during_sleep_hours']) && $data['posts_during_sleep_hours'] > 0.3) {
            $patternScore += 30;
        }

        // Check for synchronized posting with other accounts
        if (isset($data['synchronized_posting_score']) && $data['synchronized_posting_score'] > 0.7) {
            $patternScore += 30;
        }

        return min(100, $patternScore);
    }

    /**
     * Helper: Check for perfect grammar (AI indicator)
     */
    private function checkPerfectGrammar(string $content): bool
    {
        // Simplified check - in production, use NLP library
        $sentences = preg_split('/[.!?]+/', $content);
        $perfectCount = 0;

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;

            // Check for perfect sentence structure
            if (preg_match('/^[A-Z][^.!?]*[.!?]$/', $sentence . '.')) {
                $perfectCount++;
            }
        }

        $perfectRatio = count($sentences) > 0 ? $perfectCount / count($sentences) : 0;
        return $perfectRatio > 0.95; // Too perfect
    }

    /**
     * Helper: Check for repetitive structure
     */
    private function checkRepetitiveStructure(string $content): bool
    {
        $sentences = preg_split('/[.!?]+/', $content);
        if (count($sentences) < 3) return false;

        $structures = [];
        foreach ($sentences as $sentence) {
            // Get sentence structure pattern
            $words = str_word_count(trim($sentence), 1);
            $structures[] = count($words);
        }

        // Check if too many sentences have same length
        $structureCounts = array_count_values($structures);
        $maxRepetition = max($structureCounts);
        return $maxRepetition > (count($sentences) * 0.6);
    }

    /**
     * Helper: Check for generic phrasing
     */
    private function checkGenericPhrasing(string $content): bool
    {
        $genericPhrases = [
            'in conclusion', 'as we can see', 'it is important to note',
            'in summary', 'furthermore', 'additionally', 'in general',
            'it should be noted that', 'one might argue', 'as a result'
        ];

        $count = 0;
        foreach ($genericPhrases as $phrase) {
            if (stripos($content, $phrase) !== false) {
                $count++;
            }
        }

        return $count >= 3;
    }

    /**
     * Helper: Check for personality markers
     */
    private function checkPersonality(string $content): bool
    {
        $personalityMarkers = [
            'I think', 'I believe', 'in my opinion', 'personally',
            'I feel', 'my experience', 'I\'ve found', 'honestly'
        ];

        foreach ($personalityMarkers as $marker) {
            if (stripos($content, $marker) !== false) {
                return false; // Has personality
            }
        }

        return true; // Lacks personality
    }

    /**
     * Helper: Check for unnatural transitions
     */
    private function checkTransitions(string $content): bool
    {
        $transitions = [
            'however', 'therefore', 'moreover', 'furthermore',
            'consequently', 'nevertheless', 'nonetheless'
        ];

        $count = 0;
        foreach ($transitions as $transition) {
            if (stripos($content, $transition) !== false) {
                $count++;
            }
        }

        // Too many formal transitions = AI-like
        $wordCount = str_word_count($content);
        return $wordCount > 0 && ($count / ($wordCount / 100)) > 2;
    }

    /**
     * Calculate overall influence score
     *
     * @param array<string, float> $scores
     */
    private function calculateInfluenceScore(array $scores): float
    {
        $weightedSum = 0.0;

        foreach ($scores as $pattern => $score) {
            if (isset($this->influencePatterns[$pattern])) {
                $weightedSum += $score * $this->influencePatterns[$pattern]['weight'];
            }
        }

        return round($weightedSum, 1);
    }

    /**
     * Classify influence campaign type
     *
     * @param array<string, float> $scores
     * @return array<string, mixed>
     */
    private function classifyInfluenceCampaign(float $influenceScore, array $scores): array
    {
        arsort($scores);
        $primaryPattern = array_key_first($scores);

        if ($influenceScore >= 80) {
            return [
                'type' => 'coordinated_influence_campaign',
                'confidence' => $influenceScore / 100,
                'primary_pattern' => $primaryPattern,
                'description' => $this->influencePatterns[$primaryPattern]['description']
            ];
        } elseif ($influenceScore >= 60) {
            return [
                'type' => 'suspected_bot_network',
                'confidence' => $influenceScore / 100,
                'primary_pattern' => $primaryPattern,
                'description' => $this->influencePatterns[$primaryPattern]['description']
            ];
        } elseif ($influenceScore >= 40) {
            return [
                'type' => 'potential_manipulation',
                'confidence' => $influenceScore / 100,
                'primary_pattern' => $primaryPattern,
                'description' => $this->influencePatterns[$primaryPattern]['description']
            ];
        } else {
            return [
                'type' => 'authentic_content',
                'confidence' => (100 - $influenceScore) / 100,
                'primary_pattern' => 'human_generated',
                'description' => 'Genuine human-created content'
            ];
        }
    }

    /**
     * Get threat level description
     */
    private function getThreatLevel(float $score): string
    {
        if ($score >= 80) return 'CRITICAL';
        if ($score >= 60) return 'HIGH';
        if ($score >= 40) return 'MODERATE';
        if ($score >= 20) return 'LOW';
        return 'MINIMAL';
    }

    /**
     * Generate security recommendations
     *
     * @param array<string, mixed> $classification
     * @param array<string, float> $scores
     * @return array<string>
     */
    private function generateRecommendations(array $classification, array $scores): array
    {
        $recommendations = [];

        if ($classification['type'] === 'coordinated_influence_campaign') {
            $recommendations[] = 'REMOVE: High confidence coordinated campaign detected';
            $recommendations[] = 'Ban associated accounts';
            $recommendations[] = 'Report to platform security team';
            $recommendations[] = 'Investigate network for additional bot accounts';
        } elseif ($classification['type'] === 'suspected_bot_network') {
            $recommendations[] = 'FLAG: Suspicious bot activity detected';
            $recommendations[] = 'Shadow-ban to limit visibility';
            $recommendations[] = 'Monitor for campaign escalation';
        } elseif ($classification['type'] === 'potential_manipulation') {
            $recommendations[] = 'Review content manually';
            $recommendations[] = 'Add fact-check labels if misleading';
            $recommendations[] = 'Monitor account for pattern development';
        }

        // Pattern-specific recommendations
        if ($scores['synthetic_content'] > 70) {
            $recommendations[] = 'Add "AI-generated content" disclosure label';
        }

        if ($scores['authority_manipulation'] > 70) {
            $recommendations[] = 'Verify claimed credentials';
            $recommendations[] = 'Flag fake reviews for removal';
        }

        return array_unique($recommendations);
    }
}
