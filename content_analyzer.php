<?php

namespace GEOOptimizer\Analysis;

use GEOOptimizer\Exceptions\ValidationException;

/**
 * Content Analyzer for GEO Optimization
 * 
 * Analyzes content for AI search engine optimization
 */
class ContentAnalyzer
{
    private $config;
    
    // GEO-specific keywords that AI models look for
    private $geoKeywords = [
        'location', 'address', 'phone', 'contact', 'hours', 'services',
        'about', 'team', 'experience', 'years', 'professional', 'certified',
        'licensed', 'insured', 'emergency', 'availability', 'area served'
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'min_word_count' => 300,
            'target_keyword_density' => 0.02,
            'enable_readability' => true,
            'geo_weight' => 0.3
        ], $config);
    }

    /**
     * Analyze content for GEO optimization
     */
    public function analyze(string $content, array $options = []): array
    {
        if (empty(trim($content))) {
            throw new ValidationException('Content cannot be empty');
        }

        $analysis = [
            'timestamp' => date('c'),
            'content_length' => strlen($content),
            'word_count' => $this->getWordCount($content),
            'sentence_count' => $this->getSentenceCount($content),
            'paragraph_count' => $this->getParagraphCount($content),
            'geo_score' => $this->calculateGeoScore($content),
            'readability_score' => $this->calculateReadabilityScore($content),
            'keyword_analysis' => $this->analyzeKeywords($content, $options),
            'structure_analysis' => $this->analyzeStructure($content),
            'recommendations' => []
        ];

        // Calculate overall score
        $analysis['overall_score'] = $this->calculateOverallScore($analysis);

        // Generate recommendations
        $analysis['recommendations'] = $this->generateRecommendations($analysis, $content);

        return $analysis;
    }

    /**
     * Calculate GEO-specific score
     */
    private function calculateGeoScore(string $content): array
    {
        $words = $this->extractWords($content);
        $totalWords = count($words);
        
        if ($totalWords === 0) {
            return ['score' => 0, 'found_keywords' => [], 'missing_keywords' => $this->geoKeywords];
        }

        $foundKeywords = [];
        $keywordCounts = [];

        foreach ($this->geoKeywords as $keyword) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            $matches = preg_match_all($pattern, $content);
            
            if ($matches > 0) {
                $foundKeywords[] = $keyword;
                $keywordCounts[$keyword] = $matches;
            }
        }

        $geoKeywordRatio = count($foundKeywords) / count($this->geoKeywords);
        $score = min(100, $geoKeywordRatio * 100 + 20); // Bonus for having GEO keywords

        return [
            'score' => round($score, 1),
            'found_keywords' => $foundKeywords,
            'missing_keywords' => array_diff($this->geoKeywords, $foundKeywords),
            'keyword_counts' => $keywordCounts,
            'keyword_density' => count($foundKeywords) / $totalWords
        ];
    }

    /**
     * Calculate readability score (Flesch Reading Ease)
     */
    private function calculateReadabilityScore(string $content): array
    {
        if (!$this->config['enable_readability']) {
            return ['score' => null, 'level' => 'Not calculated'];
        }

        $wordCount = $this->getWordCount($content);
        $sentenceCount = $this->getSentenceCount($content);
        $syllableCount = $this->countSyllables($content);

        if ($sentenceCount === 0 || $wordCount === 0) {
            return ['score' => 0, 'level' => 'Unable to calculate'];
        }

        $avgWordsPerSentence = $wordCount / $sentenceCount;
        $avgSyllablesPerWord = $syllableCount / $wordCount;

        // Flesch Reading Ease Score
        $score = 206.835 - (1.015 * $avgWordsPerSentence) - (84.6 * $avgSyllablesPerWord);
        $score = max(0, min(100, $score)); // Clamp between 0-100

        return [
            'score' => round($score, 1),
            'level' => $this->getReadabilityLevel($score),
            'avg_words_per_sentence' => round($avgWordsPerSentence, 1),
            'avg_syllables_per_word' => round($avgSyllablesPerWord, 2)
        ];
    }

    /**
     * Analyze keywords in content
     */
    private function analyzeKeywords(string $content, array $options): array
    {
        $words = $this->extractWords($content);
        $totalWords = count($words);
        
        // Count word frequencies
        $wordFreq = array_count_values(array_map('strtolower', $words));
        arsort($wordFreq);

        // Get top keywords
        $topKeywords = array_slice($wordFreq, 0, 20);

        // Analyze target keywords if provided
        $targetAnalysis = [];
        if (!empty($options['target_keywords'])) {
            foreach ($options['target_keywords'] as $keyword) {
                $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
                $count = preg_match_all($pattern, $content);
                $density = $totalWords > 0 ? $count / $totalWords : 0;
                
                $targetAnalysis[$keyword] = [
                    'count' => $count,
                    'density' => round($density, 4),
                    'optimal' => $density >= 0.01 && $density <= 0.03
                ];
            }
        }

        return [
            'total_unique_words' => count($wordFreq),
            'top_keywords' => $topKeywords,
            'target_keywords' => $targetAnalysis,
            'keyword_diversity' => count($wordFreq) / $totalWords
        ];
    }

    /**
     * Analyze content structure
     */
    private function analyzeStructure(string $content): array
    {
        // Check for headings
        $headings = [
            'h1' => preg_match_all('/<h1[^>]*>(.*?)<\/h1>/i', $content),
            'h2' => preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $content),
            'h3' => preg_match_all('/<h3[^>]*>(.*?)<\/h3>/i', $content),
        ];

        // Check for lists
        $lists = [
            'ul' => preg_match_all('/<ul[^>]*>/i', $content),
            'ol' => preg_match_all('/<ol[^>]*>/i', $content),
            'li' => preg_match_all('/<li[^>]*>/i', $content)
        ];

        // Check for images
        $images = preg_match_all('/<img[^>]*>/i', $content);

        // Check for links
        $links = [
            'internal' => preg_match_all('/<a[^>]*href=["\'](?!http)[^"\']*["\'][^>]*>/i', $content),
            'external' => preg_match_all('/<a[^>]*href=["\']https?:\/\/[^"\']*["\'][^>]*>/i', $content)
        ];

        return [
            'headings' => $headings,
            'lists' => $lists,
            'images' => $images,
            'links' => $links,
            'has_good_structure' => $headings['h1'] > 0 && $headings['h2'] > 0
        ];
    }

    /**
     * Calculate overall optimization score
     */
    private function calculateOverallScore(array $analysis): float
    {
        $scores = [];

        // Word count score (0-100)
        $wordCount = $analysis['word_count'];
        $minWords = $this->config['min_word_count'];
        $scores['word_count'] = min(100, ($wordCount / $minWords) * 100);

        // GEO score
        $scores['geo'] = $analysis['geo_score']['score'];

        // Readability score (normalize to 0-100)
        if ($analysis['readability_score']['score'] !== null) {
            $scores['readability'] = $analysis['readability_score']['score'];
        } else {
            $scores['readability'] = 70; // Default if not calculated
        }

        // Structure score
        $structureScore = 0;
        if ($analysis['structure_analysis']['has_good_structure']) {
            $structureScore += 50;
        }
        if ($analysis['structure_analysis']['images'] > 0) {
            $structureScore += 25;
        }
        if ($analysis['structure_analysis']['lists']['ul'] > 0 || $analysis['structure_analysis']['lists']['ol'] > 0) {
            $structureScore += 25;
        }
        $scores['structure'] = $structureScore;

        // Weighted average
        $weights = [
            'word_count' => 0.2,
            'geo' => $this->config['geo_weight'],
            'readability' => 0.3,
            'structure' => 0.2
        ];

        $totalScore = 0;
        foreach ($scores as $type => $score) {
            $totalScore += $score * $weights[$type];
        }

        return round($totalScore, 1);
    }

    /**
     * Generate optimization recommendations
     */
    private function generateRecommendations(array $analysis, string $content): array
    {
        $recommendations = [];

        // Word count recommendations
        if ($analysis['word_count'] < $this->config['min_word_count']) {
            $recommendations[] = [
                'type' => 'word_count',
                'priority' => 'high',
                'message' => "Content is too short. Aim for at least {$this->config['min_word_count']} words. Current: {$analysis['word_count']} words."
            ];
        }

        // GEO recommendations
        if ($analysis['geo_score']['score'] < 60) {
            $missing = implode(', ', array_slice($analysis['geo_score']['missing_keywords'], 0, 5));
            $recommendations[] = [
                'type' => 'geo_keywords',
                'priority' => 'high',
                'message' => "Add more GEO-relevant keywords like: {$missing}"
            ];
        }

        // Structure recommendations
        if (!$analysis['structure_analysis']['has_good_structure']) {
            $recommendations[] = [
                'type' => 'structure',
                'priority' => 'medium',
                'message' => 'Add proper heading structure (H1, H2, H3) to improve content organization.'
            ];
        }

        // Readability recommendations
        if ($analysis['readability_score']['score'] !== null && $analysis['readability_score']['score'] < 50) {
            $recommendations[] = [
                'type' => 'readability',
                'priority' => 'medium',
                'message' => 'Content is difficult to read. Use shorter sentences and simpler words.'
            ];
        }

        return $recommendations;
    }

    // Helper methods
    private function getWordCount(string $content): int
    {
        return count($this->extractWords($content));
    }

    private function getSentenceCount(string $content): int
    {
        $text = strip_tags($content);
        return preg_match_all('/[.!?]+/', $text);
    }

    private function getParagraphCount(string $content): int
    {
        return preg_match_all('/<p[^>]*>/', $content) ?: substr_count($content, "\n\n") + 1;
    }

    private function extractWords(string $content): array
    {
        $text = strip_tags($content);
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        return array_filter(explode(' ', $text));
    }

    private function countSyllables(string $content): int
    {
        $words = $this->extractWords($content);
        $totalSyllables = 0;

        foreach ($words as $word) {
            $word = strtolower($word);
            $syllables = preg_match_all('/[aeiouy]+/', $word);
            $syllables = max(1, $syllables); // At least 1 syllable per word
            $totalSyllables += $syllables;
        }

        return $totalSyllables;
    }

    private function getReadabilityLevel(float $score): string
    {
        if ($score >= 90) return 'Very Easy';
        if ($score >= 80) return 'Easy';
        if ($score >= 70) return 'Fairly Easy';
        if ($score >= 60) return 'Standard';
        if ($score >= 50) return 'Fairly Difficult';
        if ($score >= 30) return 'Difficult';
        return 'Very Difficult';
    }
}