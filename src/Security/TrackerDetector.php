<?php

declare(strict_types=1);

namespace GEOOptimizer\Security;

use GEOOptimizer\Analysis\ContentAnalyzer;
use GEOOptimizer\Analytics\GEOReadinessScore;

/**
 * Tracker and Scraper Detection System
 *
 * Uses GEO analysis patterns to identify malicious trackers, scrapers,
 * and data harvesting attempts by analyzing content access patterns
 * and request characteristics
 */
class TrackerDetector
{
    private ContentAnalyzer $contentAnalyzer;
    private GEOReadinessScore $scorer;

    /**
     * Tracker behavior patterns
     * @var array<string, array<string, mixed>>
     */
    private array $trackerPatterns = [
        'rapid_sequential_access' => [
            'threshold' => 10, // requests per second
            'weight' => 0.25,
            'description' => 'Automated scraping pattern'
        ],
        'missing_geo_signals' => [
            'threshold' => 0.3, // GEO score below 30%
            'weight' => 0.20,
            'description' => 'Non-human content interaction'
        ],
        'metadata_only_access' => [
            'threshold' => 0.8, // 80% metadata vs content
            'weight' => 0.15,
            'description' => 'Harvesting structured data'
        ],
        'geographic_anomaly' => [
            'threshold' => 0.7, // Location inconsistency
            'weight' => 0.15,
            'description' => 'VPN/proxy usage pattern'
        ],
        'fingerprint_resistance' => [
            'threshold' => 0.6,
            'weight' => 0.15,
            'description' => 'Anti-tracking tools detected'
        ],
        'behavioral_inconsistency' => [
            'threshold' => 0.5,
            'weight' => 0.10,
            'description' => 'Bot-like navigation'
        ]
    ];

    public function __construct()
    {
        $this->contentAnalyzer = new ContentAnalyzer();
        $this->scorer = new GEOReadinessScore();
    }

    /**
     * Analyze request for tracker/scraper patterns
     *
     * @param array<string, mixed> $requestData
     * @return array<string, mixed>
     */
    public function analyzeRequest(array $requestData): array
    {
        $scores = [
            'rapid_sequential_access' => $this->detectRapidAccess($requestData),
            'missing_geo_signals' => $this->detectMissingGEOSignals($requestData),
            'metadata_only_access' => $this->detectMetadataHarvesting($requestData),
            'geographic_anomaly' => $this->detectGeographicAnomaly($requestData),
            'fingerprint_resistance' => $this->detectFingerprintResistance($requestData),
            'behavioral_inconsistency' => $this->detectBehavioralInconsistency($requestData)
        ];

        $threatScore = $this->calculateThreatScore($scores);
        $classification = $this->classifyRequest($threatScore, $scores);

        return [
            'threat_score' => $threatScore,
            'classification' => $classification,
            'risk_level' => $this->getRiskLevel($threatScore),
            'pattern_scores' => $scores,
            'recommendations' => $this->generateRecommendations($classification, $scores),
            'should_block' => $threatScore >= 70,
            'should_challenge' => $threatScore >= 50 && $threatScore < 70,
            'timestamp' => date('c')
        ];
    }

    /**
     * Detect rapid sequential access patterns
     */
    private function detectRapidAccess(array $data): float
    {
        if (empty($data['request_timestamps'])) {
            return 0.0;
        }

        $timestamps = $data['request_timestamps'];
        if (count($timestamps) < 2) {
            return 0.0;
        }

        // Calculate requests per second
        $timespan = max($timestamps) - min($timestamps);
        $requestsPerSecond = $timespan > 0 ? count($timestamps) / $timespan : 0;

        if ($requestsPerSecond >= $this->trackerPatterns['rapid_sequential_access']['threshold']) {
            return 100.0;
        }

        // Progressive scoring
        return min(100, ($requestsPerSecond / $this->trackerPatterns['rapid_sequential_access']['threshold']) * 100);
    }

    /**
     * Detect missing GEO optimization signals (non-human interaction)
     */
    private function detectMissingGEOSignals(array $data): float
    {
        if (!empty($data['content'])) {
            $analysis = $this->contentAnalyzer->analyzeForGEO((string) $data['content']);
            $readiness = $this->scorer->calculate(['content' => (string) $data['content']]);

            if (($analysis['overall_score'] ?? 0) < 30 || ($readiness['overall_score'] ?? 0) < 30) {
                return 75.0;
            }
        }

        if (empty($data['interaction_data'])) {
            return 50.0; // Suspicious if no interaction data
        }

        $interaction = $data['interaction_data'];
        $humanSignals = 0;
        $totalSignals = 0;

        // Check for human-like GEO interaction patterns
        $signals = [
            'hover_duration' => $interaction['hover_duration'] ?? 0,
            'scroll_depth' => $interaction['scroll_depth'] ?? 0,
            'mouse_movement_entropy' => $interaction['mouse_movement_entropy'] ?? 0,
            'reading_time_correlation' => $interaction['reading_time_correlation'] ?? 0,
            'click_precision' => $interaction['click_precision'] ?? 0
        ];

        foreach ($signals as $signal => $value) {
            $totalSignals++;
            if ($value > 0.3) { // Threshold for human-like behavior
                $humanSignals++;
            }
        }

        $humanScore = $humanSignals / $totalSignals;

        // Invert score - lower human signals = higher threat
        return (1 - $humanScore) * 100;
    }

    /**
     * Detect metadata harvesting (accessing structured data without content)
     */
    private function detectMetadataHarvesting(array $data): float
    {
        if (empty($data['access_pattern'])) {
            return 0.0;
        }

        $pattern = $data['access_pattern'];
        $metadataAccess = $pattern['metadata_requests'] ?? 0;
        $contentAccess = $pattern['content_requests'] ?? 0;
        $totalAccess = $metadataAccess + $contentAccess;

        if ($totalAccess === 0) {
            return 0.0;
        }

        $metadataRatio = $metadataAccess / $totalAccess;

        if ($metadataRatio >= $this->trackerPatterns['metadata_only_access']['threshold']) {
            return 100.0;
        }

        return $metadataRatio * 100;
    }

    /**
     * Detect geographic anomalies (VPN/proxy patterns)
     */
    private function detectGeographicAnomaly(array $data): float
    {
        if (empty($data['geographic_data'])) {
            return 0.0;
        }

        $geo = $data['geographic_data'];
        $anomalyScore = 0.0;

        // Check for IP/timezone mismatch
        if (!empty($geo['ip_country']) && !empty($geo['browser_timezone'])) {
            if ($geo['ip_country'] !== $geo['timezone_country']) {
                $anomalyScore += 40;
            }
        }

        // Check for datacenter IP ranges
        if (!empty($geo['is_datacenter']) && $geo['is_datacenter'] === true) {
            $anomalyScore += 30;
        }

        // Check for VPN indicators
        if (!empty($geo['vpn_probability']) && $geo['vpn_probability'] > 0.5) {
            $anomalyScore += 30;
        }

        return min(100, $anomalyScore);
    }

    /**
     * Detect fingerprint resistance (privacy tools)
     */
    private function detectFingerprintResistance(array $data): float
    {
        if (empty($data['fingerprint_data'])) {
            return 0.0;
        }

        $fp = $data['fingerprint_data'];
        $resistanceIndicators = 0;

        // Check for common anti-fingerprinting patterns
        if (!empty($fp['canvas_poisoning'])) $resistanceIndicators++;
        if (!empty($fp['webgl_randomization'])) $resistanceIndicators++;
        if (!empty($fp['font_randomization'])) $resistanceIndicators++;
        if (!empty($fp['user_agent_spoofing'])) $resistanceIndicators++;
        if (!empty($fp['missing_plugins'])) $resistanceIndicators++;

        return ($resistanceIndicators / 5) * 100;
    }

    /**
     * Detect behavioral inconsistencies
     */
    private function detectBehavioralInconsistency(array $data): float
    {
        if (empty($data['behavior_data'])) {
            return 0.0;
        }

        $behavior = $data['behavior_data'];
        $inconsistencyScore = 0.0;

        // Check for bot-like patterns
        if (isset($behavior['perfect_timing']) && $behavior['perfect_timing'] === true) {
            $inconsistencyScore += 25; // Humans don't have perfect timing
        }

        if (isset($behavior['no_typos']) && $behavior['no_typos'] === true && $behavior['text_input_count'] > 10) {
            $inconsistencyScore += 25; // Humans make typos
        }

        if (isset($behavior['instant_form_fill']) && $behavior['instant_form_fill'] === true) {
            $inconsistencyScore += 25; // Humans take time to fill forms
        }

        if (isset($behavior['identical_session_pattern']) && $behavior['identical_session_pattern'] === true) {
            $inconsistencyScore += 25; // Humans vary behavior
        }

        return min(100, $inconsistencyScore);
    }

    /**
     * Calculate overall threat score
     *
     * @param array<string, float> $scores
     */
    private function calculateThreatScore(array $scores): float
    {
        $weightedSum = 0.0;

        foreach ($scores as $pattern => $score) {
            if (isset($this->trackerPatterns[$pattern])) {
                $weightedSum += $score * $this->trackerPatterns[$pattern]['weight'];
            }
        }

        return round($weightedSum, 1);
    }

    /**
     * Classify request type
     *
     * @param array<string, float> $scores
     * @return array<string, mixed>
     */
    private function classifyRequest(float $threatScore, array $scores): array
    {
        // Determine primary threat type based on highest scoring pattern
        arsort($scores);
        $primaryPattern = array_key_first($scores);
        $primaryScore = $scores[$primaryPattern];

        if ($threatScore >= 70) {
            return [
                'type' => 'malicious_tracker',
                'confidence' => $threatScore / 100,
                'primary_pattern' => $primaryPattern,
                'description' => $this->trackerPatterns[$primaryPattern]['description'] ?? 'Unknown pattern'
            ];
        } elseif ($threatScore >= 50) {
            return [
                'type' => 'suspicious_bot',
                'confidence' => $threatScore / 100,
                'primary_pattern' => $primaryPattern,
                'description' => $this->trackerPatterns[$primaryPattern]['description'] ?? 'Unknown pattern'
            ];
        } elseif ($threatScore >= 30) {
            return [
                'type' => 'potential_scraper',
                'confidence' => $threatScore / 100,
                'primary_pattern' => $primaryPattern,
                'description' => $this->trackerPatterns[$primaryPattern]['description'] ?? 'Unknown pattern'
            ];
        } else {
            return [
                'type' => 'legitimate_user',
                'confidence' => (100 - $threatScore) / 100,
                'primary_pattern' => 'normal_behavior',
                'description' => 'Human-like interaction patterns'
            ];
        }
    }

    /**
     * Get risk level description
     */
    private function getRiskLevel(float $score): string
    {
        if ($score >= 80) return 'CRITICAL';
        if ($score >= 70) return 'HIGH';
        if ($score >= 50) return 'MODERATE';
        if ($score >= 30) return 'LOW';
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

        if ($classification['type'] === 'malicious_tracker') {
            $recommendations[] = 'BLOCK: High confidence malicious tracker detected';
            $recommendations[] = 'Log IP address for rate limiting';
            $recommendations[] = 'Report to threat intelligence feeds';
        } elseif ($classification['type'] === 'suspicious_bot') {
            $recommendations[] = 'CHALLENGE: Present CAPTCHA or proof-of-work';
            $recommendations[] = 'Monitor subsequent requests closely';
            $recommendations[] = 'Limit API access to read-only';
        } elseif ($classification['type'] === 'potential_scraper') {
            $recommendations[] = 'Monitor access patterns';
            $recommendations[] = 'Consider rate limiting';
            $recommendations[] = 'Verify user agent claims';
        }

        // Pattern-specific recommendations
        if ($scores['rapid_sequential_access'] > 60) {
            $recommendations[] = 'Implement progressive delays for rapid requests';
        }

        if ($scores['metadata_only_access'] > 60) {
            $recommendations[] = 'Protect structured data endpoints';
        }

        return array_unique($recommendations);
    }
}
