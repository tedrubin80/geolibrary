<?php

declare(strict_types=1);

namespace GEOOptimizer\Platforms;

/**
 * Google AI (Gemini/AI Overviews) Platform Adapter
 *
 * Handles interactions with Google's Gemini API and provides
 * optimization guidance for Google AI Overviews
 */
class GoogleAIAdapter extends AbstractPlatformAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Google AI/Gemini';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEndpoint(): string
    {
        return 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(): array
    {
        return [
            'model' => 'gemini-pro',
            'max_tokens' => 1000,
            'temperature' => 0.7,
            'timeout' => 30
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaders(): array
    {
        return [
            'Content-Type: application/json'
        ];
    }

    /**
     * Get endpoint with API key
     *
     * @return string
     */
    protected function getEndpointWithKey(): string
    {
        return $this->getEndpoint() . '?key=' . $this->apiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, array $options = []): array
    {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $query]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $options['max_tokens'] ?? $this->config['max_tokens'],
                'temperature' => $options['temperature'] ?? $this->config['temperature']
            ]
        ];

        // Override endpoint for Google's API key in URL pattern
        $ch = curl_init($this->getEndpointWithKey());

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $this->getHeaders(),
            CURLOPT_TIMEOUT => $this->config['timeout'] ?? 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true) ?? [];

        $content = '';
        if (isset($decoded['candidates'][0]['content']['parts'])) {
            foreach ($decoded['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text'])) {
                    $content .= $part['text'];
                }
            }
        }

        return [
            'platform' => $this->getName(),
            'query' => $query,
            'content' => $content,
            'model' => $this->config['model'],
            'timestamp' => time()
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPlatformSpecificTips(array $contentAnalysis): array
    {
        $tips = [];

        // Google AI Overviews pulls from search results
        $tips[] = [
            'priority' => 'critical',
            'category' => 'google_ai_specific',
            'tip' => 'Ensure strong traditional SEO - Google AI Overviews uses Search index',
            'platform' => $this->getName()
        ];

        // Schema markup is crucial for Google
        $tips[] = [
            'priority' => 'high',
            'category' => 'google_ai_specific',
            'tip' => 'Implement comprehensive Schema.org markup for rich snippets',
            'platform' => $this->getName()
        ];

        // E-E-A-T signals
        if (($contentAnalysis['authority_signals']['score'] ?? 0) < 70) {
            $tips[] = [
                'priority' => 'high',
                'category' => 'google_ai_specific',
                'tip' => 'Strengthen E-E-A-T signals: Experience, Expertise, Authoritativeness, Trust',
                'platform' => $this->getName()
            ];
        }

        // Featured snippet optimization
        if (!($contentAnalysis['structure_quality']['has_lists'] ?? false)) {
            $tips[] = [
                'priority' => 'medium',
                'category' => 'google_ai_specific',
                'tip' => 'Format content for featured snippets (lists, tables, definitions)',
                'platform' => $this->getName()
            ];
        }

        return $tips;
    }

    /**
     * Analyze content for Google AI Overview optimization
     *
     * @param string $content The content to analyze
     * @return array<string, mixed>
     */
    public function analyzeAIOverviewReadiness(string $content): array
    {
        $score = 0;
        $factors = [];

        // Google values E-E-A-T (Experience, Expertise, Authoritativeness, Trust)
        $eeatSignals = $this->analyzeEEAT($content);
        $score += $eeatSignals['score'] * 0.3;
        $factors['eeat'] = $eeatSignals;

        // Featured snippet potential
        $snippetReadiness = $this->analyzeSnippetPotential($content);
        $score += $snippetReadiness['score'] * 0.25;
        $factors['snippet_potential'] = $snippetReadiness;

        // Schema-friendly structure
        $structureScore = $this->analyzeStructureForGoogle($content);
        $score += $structureScore * 0.25;
        $factors['structure_score'] = $structureScore;

        // Query-answer alignment
        $qaScore = $this->analyzeQAPotential($content);
        $score += $qaScore * 0.2;
        $factors['qa_alignment'] = $qaScore;

        return [
            'ai_overview_readiness_score' => round($score, 1),
            'factors' => $factors,
            'recommendations' => $this->getGoogleAIRecommendations($factors),
            'schema_suggestions' => $this->getSchemaSuggestions($content)
        ];
    }

    /**
     * Analyze E-E-A-T signals
     *
     * @return array<string, mixed>
     */
    private function analyzeEEAT(string $content): array
    {
        $score = 0;
        $signals = [];

        // Experience signals
        $experienceWords = ['in our experience', 'we have found', 'years of', 'hands-on', 'firsthand'];
        foreach ($experienceWords as $word) {
            if (stripos($content, $word) !== false) {
                $score += 5;
                $signals['experience'][] = $word;
            }
        }

        // Expertise signals
        $expertiseWords = ['certified', 'licensed', 'qualified', 'specialist', 'expert', 'professional'];
        foreach ($expertiseWords as $word) {
            if (stripos($content, $word) !== false) {
                $score += 5;
                $signals['expertise'][] = $word;
            }
        }

        // Authority signals
        $authorityWords = ['award', 'recognized', 'featured in', 'industry leader', 'trusted by'];
        foreach ($authorityWords as $word) {
            if (stripos($content, $word) !== false) {
                $score += 5;
                $signals['authority'][] = $word;
            }
        }

        // Trust signals
        $trustWords = ['guarantee', 'verified', 'secure', 'transparent', 'honest'];
        foreach ($trustWords as $word) {
            if (stripos($content, $word) !== false) {
                $score += 5;
                $signals['trust'][] = $word;
            }
        }

        return [
            'score' => min(100, $score),
            'signals' => $signals
        ];
    }

    /**
     * Analyze featured snippet potential
     *
     * @return array<string, mixed>
     */
    private function analyzeSnippetPotential(string $content): array
    {
        $score = 0;
        $types = [];

        // Definition snippets
        if (preg_match('/\b(is|are|means|refers to)\b.*\./i', $content)) {
            $score += 25;
            $types[] = 'definition';
        }

        // List snippets
        if (preg_match('/^\d+\.\s+|^[\*\-]\s+/m', $content)) {
            $score += 25;
            $types[] = 'list';
        }

        // Table potential
        if (preg_match('/\|.*\|/m', $content) || preg_match('/<table/i', $content)) {
            $score += 25;
            $types[] = 'table';
        }

        // How-to/step format
        if (preg_match('/step\s*\d|how to/i', $content)) {
            $score += 25;
            $types[] = 'how-to';
        }

        return [
            'score' => min(100, $score),
            'snippet_types' => $types
        ];
    }

    /**
     * Analyze structure for Google
     */
    private function analyzeStructureForGoogle(string $content): float
    {
        $score = 0;

        // Headers
        if (preg_match('/^#{1,3}\s+/m', $content)) $score += 25;

        // Short paragraphs (Google likes digestible content)
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $avgLength = array_sum(array_map('strlen', $paragraphs)) / max(1, count($paragraphs));
        if ($avgLength < 500) $score += 25;

        // Semantic clarity
        if (preg_match('/^(what|how|why|when|where|who)/im', $content)) $score += 25;

        // Concise answers within content
        if (strlen($content) >= 300 && strlen($content) <= 2000) $score += 25;

        return min(100, $score);
    }

    /**
     * Analyze Q&A potential
     */
    private function analyzeQAPotential(string $content): float
    {
        $score = 0;

        // Question presence
        $questions = preg_match_all('/\?/m', $content);
        if ($questions > 0) $score += 30;

        // Answer indicators
        $answerWords = ['answer', 'solution', 'result', 'here is', 'the following'];
        foreach ($answerWords as $word) {
            if (stripos($content, $word) !== false) {
                $score += 14;
            }
        }

        return min(100, $score);
    }

    /**
     * Get Google AI-specific recommendations
     *
     * @param array<string, mixed> $factors
     * @return array<string>
     */
    private function getGoogleAIRecommendations(array $factors): array
    {
        $recommendations = [];

        if (($factors['eeat']['score'] ?? 0) < 50) {
            $recommendations[] = 'Add more E-E-A-T signals: credentials, experience statements, trust indicators';
        }

        if (($factors['snippet_potential']['score'] ?? 0) < 50) {
            $recommendations[] = 'Format content for featured snippets: definitions, lists, or tables';
        }

        if (($factors['structure_score'] ?? 0) < 50) {
            $recommendations[] = 'Improve content structure with clear headers and shorter paragraphs';
        }

        if (($factors['qa_alignment'] ?? 0) < 50) {
            $recommendations[] = 'Include clear questions and direct answers';
        }

        return $recommendations;
    }

    /**
     * Get Schema.org suggestions based on content
     *
     * @return array<string>
     */
    private function getSchemaSuggestions(string $content): array
    {
        $suggestions = [];

        // Always recommend basic types
        $suggestions[] = 'Organization or LocalBusiness schema';

        // Conditional suggestions
        if (preg_match('/FAQ|question|answer/i', $content)) {
            $suggestions[] = 'FAQPage schema for Q&A content';
        }

        if (preg_match('/how to|step|guide/i', $content)) {
            $suggestions[] = 'HowTo schema for instructional content';
        }

        if (preg_match('/review|rating|stars/i', $content)) {
            $suggestions[] = 'Review or AggregateRating schema';
        }

        if (preg_match('/article|blog|news/i', $content)) {
            $suggestions[] = 'Article schema with author information';
        }

        return $suggestions;
    }
}
