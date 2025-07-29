<?php

namespace GEOOptimizer\Analysis;

use GEOOptimizer\Exceptions\ValidationException;

/**
 * Content Analyzer for GEO Optimization
 * 
 * Analyzes content for AI-friendliness and citation potential
 */
class ContentAnalyzer
{
    private $config;
    private $authorityKeywords = [
        'certified', 'licensed', 'accredited', 'award-winning', 'experienced',
        'expert', 'professional', 'established', 'trusted', 'years of experience',
        'ISO certified', 'BBB rated', 'industry leader', 'recognized'
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'min_word_count' => 100,
            'max_sentence_length' => 25,
            'authority_weight' => 0.3,
            'structure_weight' => 0.4,
            'completeness_weight' => 0.3
        ], $config);
    }

    /**
     * Comprehensive GEO analysis of content
     *
     * @param string $content Content to analyze
     * @param array $metadata Additional metadata
     * @return array Analysis results
     */
    public function analyzeForGEO(string $content, array $metadata = []): array
    {
        if (empty($content)) {
            throw new ValidationException('Content cannot be empty');
        }

        $analysis = [
            'overall_score' => 0,
            'readability' => $this->analyzeReadability($content),
            'authority_signals' => $this->detectAuthoritySignals($content),
            'structure_quality' => $this->analyzeStructure($content),
            'citation_potential' => $this->assessCitationPotential($content, $metadata),
            'completeness' => $this->assessCompleteness($content, $metadata),
            'improvements' => []
        ];

        // Calculate overall score
        $analysis['overall_score'] = $this->calculateOverallScore($analysis);
        
        // Generate improvement suggestions
        $analysis['improvements'] = $this->generateImprovements($analysis, $content);

        return $analysis;
    }

    /**
     * Analyze content readability for AI consumption
     *
     * @param string $content
     * @return array Readability metrics
     */
    private function analyzeReadability(string $content): array
    {
        $sentences = $this->splitIntoSentences($content);
        $words = str_word_count($content);
        $sentenceCount = count($sentences);
        
        $avgWordsPerSentence = $sentenceCount > 0 ? $words / $sentenceCount : 0;
        $longSentences = array_filter($sentences, function($sentence) {
            return str_word_count($sentence) > $this->config['max_sentence_length'];
        });

        $readabilityScore = $this->calculateReadabilityScore($avgWordsPerSentence, count($longSentences), $sentenceCount);

        return [
            'score' => $readabilityScore,
            'word_count' => $words,
            'sentence_count' => $sentenceCount,
            'avg_words_per_sentence' => round($avgWordsPerSentence, 1),
            'long_sentences_count' => count($longSentences),
            'reading_level' => $this->getReadingLevel($readabilityScore),
            'ai_friendly' => $readabilityScore >= 70
        ];
    }

    /**
     * Detect authority signals in content
     *
     * @param string $content
     * @return array Authority analysis
     */
    private function detectAuthoritySignals(string $content): array
    {
        $content_lower = strtolower($content);
        $foundSignals = [];
        $signalCount = 0;

        foreach ($this->authorityKeywords as $keyword) {
            if (strpos($content_lower, strtolower($keyword)) !== false) {
                $foundSignals[] = $keyword;
                $signalCount++;
            }
        }

        // Look for specific patterns
        $patterns = [
            'years_experience' => '/(\d+)\s*years?\s*(of\s*)?(experience|in business)/i',
            'certifications' => '/(certified|licensed|accredited)\s*(by|in|for)/i',
            'awards' => '/(award|recognition|winner|rated|ranked)/i',
            'numbers_stats' => '/(\d+[%]?|\d+[k]?\+?)\s*(clients|customers|projects|years)/i'
        ];

        $patternMatches = [];
        foreach ($patterns as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $patternMatches[$type] = $matches[0];
            }
        }

        $authorityScore = min(100, ($signalCount * 10) + (count($patternMatches) * 15));

        return [
            'score' => $authorityScore,
            'signals_found' => $foundSignals,
            'signal_count' => $signalCount,
            'pattern_matches' => $patternMatches,
            'has_credentials' => $signalCount >= 3,
            'has_experience_data' => isset($patternMatches['years_experience']),
            'has_social_proof' => isset($patternMatches['numbers_stats'])
        ];
    }

    /**
     * Analyze content structure for AI parsing
     *
     * @param string $content
     * @return array Structure analysis
     */
    private function analyzeStructure(string $content): array
    {
        // Check for common structural elements
        $hasHeadings = preg_match('/^#{1,6}\s+.+$/m', $content) > 0;
        $hasBulletPoints = preg_match('/^[\*\-\+]\s+.+$/m', $content) > 0;
        $hasNumberedList = preg_match('/^\d+\.\s+.+$/m', $content) > 0;
        $hasQuestions = preg_match('/\?[^\w]/m', $content) > 0;
        
        // Analyze paragraphs
        $paragraphs = preg_split('/\n\s*\n/', trim($content));
        $paragraphCount = count($paragraphs);
        $avgParagraphLength = array_sum(array_map('str_word_count', $paragraphs)) / $paragraphCount;

        // Check for FAQ-style content
        $faqPatterns = preg_match_all('/^(what|how|why|when|where|who)\s+.*\?/im', $content);
        
        $structureScore = 0;
        if ($hasHeadings) $structureScore += 20;
        if ($hasBulletPoints || $hasNumberedList) $structureScore += 15;
        if ($hasQuestions) $structureScore += 10;
        if ($faqPatterns > 0) $structureScore += 20;
        if ($avgParagraphLength < 50) $structureScore += 15;
        if ($paragraphCount >= 3) $structureScore += 20;

        return [
            'score' => min(100, $structureScore),
            'has_headings' => $hasHeadings,
            'has_lists' => $hasBulletPoints || $hasNumberedList,
            'has_questions' => $hasQuestions,
            'faq_questions' => $faqPatterns,
            'paragraph_count' => $paragraphCount,
            'avg_paragraph_length' => round($avgParagraphLength, 1),
            'well_structured' => $structureScore >= 60
        ];
    }

    /**
     * Assess citation potential
     *
     * @param string $content
     * @param array $metadata
     * @return array Citation assessment
     */
    private function assessCitationPotential(string $content, array $metadata = []): array
    {
        $factors = [
            'specificity' => $this->assessSpecificity($content),
            'completeness' => $this->hasCompleteAnswers($content),
            'actionability' => $this->hasActionableInfo($content),
            'uniqueness' => $this->assessUniqueness($content, $metadata),
            'freshness' => $this->assessFreshness($metadata)
        ];

        $citationScore = array_sum($factors) / count($factors);

        return [
            'score' => round($citationScore, 1),
            'factors' => $factors,
            'likely_to_be_cited' => $citationScore >= 75,
            'citation_triggers' => $this->findCitationTriggers($content)
        ];
    }

    /**
     * Assess content completeness
     *
     * @param string $content
     * @param array $metadata
     * @return array Completeness assessment
     */
    private function assessCompleteness(string $content, array $metadata = []): array
    {
        $businessType = $metadata['business_type'] ?? 'general';
        $requiredElements = $this->getRequiredElements($businessType);
        
        $foundElements = [];
        $missingElements = [];

        foreach ($requiredElements as $element => $patterns) {
            $found = false;
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, strtolower($content))) {
                    $foundElements[] = $element;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missingElements[] = $element;
            }
        }

        $completenessScore = (count($foundElements) / count($requiredElements)) * 100;

        return [
            'score' => round($completenessScore, 1),
            'found_elements' => $foundElements,
            'missing_elements' => $missingElements,
            'total_required' => count($requiredElements),
            'is_comprehensive' => $completenessScore >= 80
        ];
    }

    /**
     * Calculate overall GEO score
     *
     * @param array $analysis
     * @return float Overall score
     */
    private function calculateOverallScore(array $analysis): float
    {
        $weights = [
            'readability' => 0.25,
            'authority_signals' => 0.25,
            'structure_quality' => 0.25,
            'citation_potential' => 0.25
        ];

        $score = 0;
        foreach ($weights as $component => $weight) {
            $score += $analysis[$component]['score'] * $weight;
        }

        return round($score, 1);
    }

    /**
     * Generate improvement suggestions
     *
     * @param array $analysis
     * @param string $content
     * @return array Suggestions
     */
    private function generateImprovements(array $analysis, string $content): array
    {
        $improvements = [];

        // Readability improvements
        if ($analysis['readability']['score'] < 70) {
            if ($analysis['readability']['avg_words_per_sentence'] > 20) {
                $improvements[] = [
                    'type' => 'readability',
                    'priority' => 'high',
                    'message' => 'Break up long sentences (avg: ' . $analysis['readability']['avg_words_per_sentence'] . ' words)',
                    'suggestion' => 'Aim for sentences under 20 words for better AI comprehension'
                ];
            }
            
            if ($analysis['readability']['word_count'] < $this->config['min_word_count']) {
                $improvements[] = [
                    'type' => 'readability',
                    'priority' => 'medium',
                    'message' => 'Content is too short (' . $analysis['readability']['word_count'] . ' words)',
                    'suggestion' => 'Add more detailed information to improve authority'
                ];
            }
        }

        // Authority improvements
        if ($analysis['authority_signals']['score'] < 60) {
            $improvements[] = [
                'type' => 'authority',
                'priority' => 'high',
                'message' => 'Add more authority signals',
                'suggestion' => 'Include certifications, years of experience, awards, or customer testimonials'
            ];
        }

        // Structure improvements
        if (!$analysis['structure_quality']['well_structured']) {
            if (!$analysis['structure_quality']['has_headings']) {
                $improvements[] = [
                    'type' => 'structure',
                    'priority' => 'high',
                    'message' => 'Add clear headings',
                    'suggestion' => 'Use H2/H3 headings to organize content for AI parsing'
                ];
            }
            
            if (!$analysis['structure_quality']['has_lists']) {
                $improvements[] = [
                    'type' => 'structure',
                    'priority' => 'medium',
                    'message' => 'Add bullet points or numbered lists',
                    'suggestion' => 'Lists make information easier for AI to extract and cite'
                ];
            }
        }

        // Citation potential improvements
        if ($analysis['citation_potential']['score'] < 70) {
            $improvements[] = [
                'type' => 'citation',
                'priority' => 'high',
                'message' => 'Improve citation potential',
                'suggestion' => 'Add specific examples, statistics, or step-by-step instructions'
            ];
        }

        return $improvements;
    }

    // Helper methods
    private function splitIntoSentences(string $content): array
    {
        return preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function calculateReadabilityScore(float $avgWords, int $longSentences, int $totalSentences): float
    {
        $baseScore = 100;
        $baseScore -= ($avgWords - 15) * 2; // Penalty for long sentences
        $baseScore -= ($longSentences / $totalSentences) * 30; // Penalty for long sentence ratio
        return max(0, min(100, $baseScore));
    }

    private function getReadingLevel(float $score): string
    {
        if ($score >= 80) return 'Very Easy';
        if ($score >= 70) return 'Easy';
        if ($score >= 60) return 'Fairly Easy';
        if ($score >= 50) return 'Standard';
        if ($score >= 40) return 'Fairly Difficult';
        return 'Difficult';
    }

    private function assessSpecificity(string $content): float
    {
        $specificWords = ['specific', 'exactly', 'precisely', 'detailed', 'step-by-step'];
        $numbers = preg_match_all('/\d+/', $content);
        $specificWordCount = 0;
        
        foreach ($specificWords as $word) {
            if (stripos($content, $word) !== false) {
                $specificWordCount++;
            }
        }
        
        return min(100, ($numbers * 5) + ($specificWordCount * 10));
    }

    private function hasCompleteAnswers(string $content): float
    {
        $questionWords = ['what', 'how', 'why', 'when', 'where', 'who'];
        $answerWords = ['because', 'therefore', 'result', 'solution', 'answer'];
        
        $questions = 0;
        $answers = 0;
        
        foreach ($questionWords as $word) {
            $questions += substr_count(strtolower($content), $word);
        }
        
        foreach ($answerWords as $word) {
            $answers += substr_count(strtolower($content), $word);
        }
        
        return $questions > 0 ? min(100, ($answers / $questions) * 100) : 50;
    }

    private function hasActionableInfo(string $content): float
    {
        $actionWords = ['how to', 'step', 'process', 'method', 'technique', 'guide', 'tutorial'];
        $actionCount = 0;
        
        foreach ($actionWords as $word) {
            if (stripos($content, $word) !== false) {
                $actionCount++;
            }
        }
        
        return min(100, $actionCount * 15);
    }

    private function assessUniqueness(string $content, array $metadata): float
    {
        // Basic uniqueness assessment
        $uniqueFactors = [
            'local_references' => $this->hasLocalReferences($content, $metadata),
            'personal_experience' => $this->hasPersonalExperience($content),
            'specific_examples' => $this->hasSpecificExamples($content)
        ];
        
        return array_sum($uniqueFactors) / count($uniqueFactors);
    }

    private function assessFreshness(array $metadata): float
    {
        if (!isset($metadata['last_updated'])) {
            return 50; // Neutral if no date provided
        }
        
        $lastUpdated = strtotime($metadata['last_updated']);
        $now = time();
        $daysSince = ($now - $lastUpdated) / (60 * 60 * 24);
        
        if ($daysSince <= 30) return 100;
        if ($daysSince <= 90) return 80;
        if ($daysSince <= 180) return 60;
        if ($daysSince <= 365) return 40;
        return 20;
    }

    private function hasLocalReferences(string $content, array $metadata): float
    {
        $location = $metadata['location'] ?? '';
        if (empty($location)) return 0;
        
        return stripos($content, $location) !== false ? 100 : 0;
    }

    private function hasPersonalExperience(string $content): float
    {
        $experienceWords = ['our experience', 'we have', 'in our', 'we specialize', 'we provide'];
        $count = 0;
        
        foreach ($experienceWords as $phrase) {
            if (stripos($content, $phrase) !== false) {
                $count++;
            }
        }
        
        return min(100, $count * 25);
    }

    private function hasSpecificExamples(string $content): float
    {
        $exampleWords = ['for example', 'such as', 'including', 'specifically', 'case study'];
        $count = 0;
        
        foreach ($exampleWords as $phrase) {
            if (stripos($content, $phrase) !== false) {
                $count++;
            }
        }
        
        return min(100, $count * 20);
    }

    private function findCitationTriggers(string $content): array
    {
        $triggers = [];
        
        // Check for definitive statements
        if (preg_match('/^(the|these are|here are|this is)/im', $content)) {
            $triggers[] = 'Definitive statements';
        }
        
        // Check for lists
        if (preg_match('/^\d+\./m', $content) || preg_match('/^[\*\-]/m', $content)) {
            $triggers[] = 'Structured lists';
        }
        
        // Check for statistics
        if (preg_match('/\d+%|\d+\s*(percent|million|billion)/', $content)) {
            $triggers[] = 'Statistics and numbers';
        }
        
        return $triggers;
    }

    private function getRequiredElements(string $businessType): array
    {
        $common = [
            'contact_info' => ['/phone|email|contact|address/i'],
            'services' => ['/services|we offer|we provide/i'],
            'location' => ['/location|address|serve|area/i']
        ];

        $specific = [
            'restaurant' => [
                'hours' => ['/hours|open|closed/i'],
                'menu' => ['/menu|food|cuisine/i'],
                'booking' => ['/reservation|booking/i']
            ],
            'medical' => [
                'credentials' => ['/doctor|md|licensed|certified/i'],
                'insurance' => ['/insurance|accepted/i'],
                'appointments' => ['/appointment|schedule/i']
            ],
            'legal' => [
                'practice_areas' => ['/practice|law|legal/i'],
                'experience' => ['/years|experience|cases/i'],
                'consultation' => ['/consultation|free/i']
            ]
        ];

        return array_merge($common, $specific[$businessType] ?? []);
    }
}