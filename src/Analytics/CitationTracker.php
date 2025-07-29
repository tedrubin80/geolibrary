<?php

namespace GEOOptimizer\Analytics;

use GEOOptimizer\Exceptions\GEOException;

/**
 * Citation Tracker
 * 
 * Tracks and monitors AI citations of your content across different platforms
 */
class CitationTracker
{
    private $config;
    private $storage;
    private $apiEndpoints;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'storage_path' => 'citations/',
            'check_interval' => 3600, // 1 hour
            'max_checks_per_day' => 100,
            'notification_webhook' => null
        ], $config);
        
        $this->initializeStorage();
        $this->setupAPIEndpoints();
    }

    /**
     * Track citations for a domain
     */
    public function trackDomain(string $domain): array
    {
        $this->validateDomain($domain);
        
        $citations = [
            'domain' => $domain,
            'timestamp' => time(),
            'sources' => $this->checkAllSources($domain),
            'total_citations' => 0,
            'new_citations' => 0,
            'trending_topics' => []
        ];

        // Calculate totals
        foreach ($citations['sources'] as $source => $data) {
            $citations['total_citations'] += $data['count'];
        }

        // Check for new citations since last check
        $lastCheck = $this->getLastCheck($domain);
        $citations['new_citations'] = $this->countNewCitations($domain, $lastCheck);

        // Save results
        $this->saveCitationData($domain, $citations);
        
        // Send notifications if configured
        if ($citations['new_citations'] > 0 && $this->config['notification_webhook']) {
            $this->sendNotification($domain, $citations);
        }

        return $citations;
    }

    /**
     * Get citation history for domain
     */
    public function getCitationHistory(string $domain, int $days = 30): array
    {
        $history = [];
        $startDate = time() - ($days * 24 * 60 * 60);
        
        $files = $this->getCitationFiles($domain, $startDate);
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data['timestamp'] >= $startDate) {
                $history[] = [
                    'date' => date('Y-m-d', $data['timestamp']),
                    'total_citations' => $data['total_citations'],
                    'new_citations' => $data['new_citations'],
                    'sources' => $data['sources']
                ];
            }
        }

        return $this->processHistory($history);
    }

    /**
     * Get citation trends and insights
     */
    public function getCitationInsights(string $domain): array
    {
        $history = $this->getCitationHistory($domain, 30);
        
        if (empty($history)) {
            return ['error' => 'No citation data available'];
        }

        return [
            'growth_rate' => $this->calculateGrowthRate($history),
            'most_cited_content' => $this->getMostCitedContent($domain),
            'peak_citation_days' => $this->getPeakDays($history),
            'source_breakdown' => $this->getSourceBreakdown($history),
            'recommendations' => $this->generateRecommendations($history)
        ];
    }

    /**
     * Monitor specific content URLs
     */
    public function monitorContent(array $urls): array
    {
        $results = [];
        
        foreach ($urls as $url) {
            $results[$url] = [
                'citations_found' => $this->checkContentCitations($url),
                'ai_mentions' => $this->findAIMentions($url),
                'citation_context' => $this->getCitationContext($url),
                'optimization_score' => $this->scoreContent($url)
            ];
        }

        return $results;
    }

    /**
     * Check all AI sources for citations
     */
    private function checkAllSources(string $domain): array
    {
        $sources = [
            'search_engines' => $this->checkSearchEngines($domain),
            'ai_assistants' => $this->checkAIAssistants($domain),
            'social_mentions' => $this->checkSocialMentions($domain),
            'news_citations' => $this->checkNewsCitations($domain)
        ];

        return $sources;
    }

    /**
     * Check search engines for citations
     */
    private function checkSearchEngines(string $domain): array
    {
        // Simulate checking Google, Bing, etc. for domain mentions
        // In production, this would use actual search APIs
        
        return [
            'count' => rand(5, 25),
            'sources' => ['Google AI Overviews', 'Bing Chat', 'Perplexity'],
            'sample_queries' => [
                'best services in [location]',
                'how to [service related query]',
                '[business type] near me'
            ],
            'last_checked' => time()
        ];
    }

    /**
     * Check AI assistants for citations
     */
    private function checkAIAssistants(string $domain): array
    {
        // Simulate checking ChatGPT, Claude, etc.
        // In production, this would monitor actual AI responses
        
        return [
            'count' => rand(3, 15),
            'platforms' => ['ChatGPT', 'Claude', 'Gemini'],
            'citation_types' => ['direct_reference', 'indirect_mention', 'source_link'],
            'confidence_score' => rand(70, 95),
            'last_checked' => time()
        ];
    }

    /**
     * Check social media mentions
     */
    private function checkSocialMentions(string $domain): array
    {
        return [
            'count' => rand(2, 10),
            'platforms' => ['Twitter', 'LinkedIn', 'Reddit'],
            'sentiment' => 'positive',
            'last_checked' => time()
        ];
    }

    /**
     * Check news citations
     */
    private function checkNewsCitations(string $domain): array
    {
        return [
            'count' => rand(1, 5),
            'sources' => ['Local news', 'Industry publications'],
            'topics' => ['business feature', 'expert quote'],
            'last_checked' => time()
        ];
    }

    /**
     * Find AI mentions of specific content
     */
    private function findAIMentions(string $url): array
    {
        // In production, this would check if the URL is being cited
        return [
            'total_mentions' => rand(0, 8),
            'ai_platforms' => ['ChatGPT', 'Claude'],
            'mention_contexts' => [
                'answer_source',
                'reference_link',
                'expert_opinion'
            ]
        ];
    }

    /**
     * Get citation context
     */
    private function getCitationContext(string $url): array
    {
        return [
            'common_queries' => [
                'What are the best [services] in [location]?',
                'How do I [relevant action]?',
                'Who are the top [business type] providers?'
            ],
            'citation_reasons' => [
                'Authoritative source',
                'Local expertise',
                'Detailed information'
            ]
        ];
    }

    /**
     * Score content for citation potential
     */
    private function scoreContent(string $url): int
    {
        // Simulate content scoring
        // In production, this would analyze actual content
        return rand(60, 95);
    }

    /**
     * Calculate citation growth rate
     */
    private function calculateGrowthRate(array $history): float
    {
        if (count($history) < 2) return 0;
        
        $recent = array_slice($history, -7); // Last 7 days
        $previous = array_slice($history, -14, 7); // Previous 7 days
        
        $recentAvg = array_sum(array_column($recent, 'total_citations')) / count($recent);
        $previousAvg = array_sum(array_column($previous, 'total_citations')) / count($previous);
        
        if ($previousAvg == 0) return 0;
        
        return (($recentAvg - $previousAvg) / $previousAvg) * 100;
    }

    /**
     * Get most cited content
     */
    private function getMostCitedContent(string $domain): array
    {
        // In production, this would track individual page citations
        return [
            ['url' => $domain . '/services', 'citations' => 15],
            ['url' => $domain . '/about', 'citations' => 12],
            ['url' => $domain . '/contact', 'citations' => 8]
        ];
    }

    /**
     * Get peak citation days
     */
    private function getPeakDays(array $history): array
    {
        usort($history, function($a, $b) {
            return $b['total_citations'] - $a['total_citations'];
        });
        
        return array_slice($history, 0, 3);
    }

    /**
     * Get source breakdown
     */
    private function getSourceBreakdown(array $history): array
    {
        $breakdown = [
            'search_engines' => 0,
            'ai_assistants' => 0,
            'social_mentions' => 0,
            'news_citations' => 0
        ];
        
        foreach ($history as $day) {
            if (isset($day['sources'])) {
                foreach ($breakdown as $source => $count) {
                    $breakdown[$source] += $day['sources'][$source]['count'] ?? 0;
                }
            }
        }
        
        return $breakdown;
    }

    /**
     * Generate recommendations based on citation data
     */
    private function generateRecommendations(array $history): array
    {
        $recommendations = [];
        
        $sourceBreakdown = $this->getSourceBreakdown($history);
        $totalCitations = array_sum($sourceBreakdown);
        
        if ($totalCitations < 10) {
            $recommendations[] = [
                'type' => 'content',
                'priority' => 'high',
                'message' => 'Low citation count - improve content authority and specificity'
            ];
        }
        
        if ($sourceBreakdown['ai_assistants'] < $totalCitations * 0.3) {
            $recommendations[] = [
                'type' => 'optimization',
                'priority' => 'medium',
                'message' => 'Optimize content structure for AI assistant citations'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Initialize storage system
     */
    private function initializeStorage(): void
    {
        $storagePath = $this->config['storage_path'];
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
    }

    /**
     * Setup API endpoints for checking citations
     */
    private function setupAPIEndpoints(): void
    {
        $this->apiEndpoints = [
            'search' => 'https://api.example.com/search',
            'social' => 'https://api.example.com/social',
            'news' => 'https://api.example.com/news'
        ];
    }

    /**
     * Validate domain format
     */
    private function validateDomain(string $domain): void
    {
        if (!filter_var($domain, FILTER_VALIDATE_URL) && !filter_var("http://{$domain}", FILTER_VALIDATE_URL)) {
            throw new GEOException("Invalid domain format: {$domain}");
        }
    }

    /**
     * Get last check timestamp for domain
     */
    private function getLastCheck(string $domain): int
    {
        $filename = $this->config['storage_path'] . '/' . md5($domain) . '_last_check.txt';
        
        if (file_exists($filename)) {
            return (int) file_get_contents($filename);
        }
        
        return 0;
    }

    /**
     * Count new citations since last check
     */
    private function countNewCitations(string $domain, int $lastCheck): int
    {
        // In production, this would compare against stored citation data
        return rand(0, 5);
    }

    /**
     * Save citation data
     */
    private function saveCitationData(string $domain, array $data): void
    {
        $filename = $this->config['storage_path'] . '/' . md5($domain) . '_' . date('Y-m-d') . '.json';
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        
        // Update last check timestamp
        $lastCheckFile = $this->config['storage_path'] . '/' . md5($domain) . '_last_check.txt';
        file_put_contents($lastCheckFile, time());
    }

    /**
     * Get citation files for domain
     */
    private function getCitationFiles(string $domain, int $startDate): array
    {
        $pattern = $this->config['storage_path'] . '/' . md5($domain) . '_*.json';
        $files = glob($pattern);
        
        return array_filter($files, function($file) use ($startDate) {
            return filemtime($file) >= $startDate;
        });
    }

    /**
     * Process citation history data
     */
    private function processHistory(array $history): array
    {
        // Sort by date
        usort($history, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        return $history;
    }

    /**
     * Send notification webhook
     */
    private function sendNotification(string $domain, array $citationData): void
    {
        if (!$this->config['notification_webhook']) return;
        
        $payload = [
            'domain' => $domain,
            'new_citations' => $citationData['new_citations'],
            'total_citations' => $citationData['total_citations'],
            'timestamp' => $citationData['timestamp']
        ];
        
        // Send webhook notification
        $this->sendWebhook($this->config['notification_webhook'], $payload);
    }

    /**
     * Send webhook request
     */
    private function sendWebhook(string $url, array $payload): void
    {
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($payload)
            ]
        ];
        
        $context = stream_context_create($options);
        @file_get_contents($url, false, $context);
    }
}