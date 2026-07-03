<?php

declare(strict_types=1);

namespace GEOOptimizer\Analytics;

/**
 * Aggregates citation tracking data for dashboard views.
 */
class CitationDashboard
{
    private CitationTracker $tracker;

    public function __construct(?CitationTracker $tracker = null)
    {
        $this->tracker = $tracker ?? new CitationTracker();
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboard(string $identifier, int $days = 30): array
    {
        $history = $this->tracker->getHistory($identifier, $days);
        $insights = $this->tracker->getInsights($identifier);

        return [
            'identifier' => $identifier,
            'period_days' => $days,
            'has_data' => $history !== [],
            'history' => $history,
            'insights' => $insights,
            'chart' => $this->buildChartSeries($history),
        ];
    }

    /**
     * @return list<string>
     */
    public function listIdentifiers(): array
    {
        $storagePath = sys_get_temp_dir() . '/geo_citations';
        if (!is_dir($storagePath)) {
            return [];
        }

        $identifiers = [];
        foreach (glob($storagePath . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $identifiers[] = basename($directory);
        }

        sort($identifiers);

        return $identifiers;
    }

    /**
     * @param list<array<string, mixed>> $history
     * @return list<array{date: string, score: float|int, citations: int}>
     */
    private function buildChartSeries(array $history): array
    {
        $series = [];

        foreach ($history as $point) {
            $series[] = [
                'date' => (string) ($point['date'] ?? ''),
                'score' => $point['geo_readiness_score'] ?? 0,
                'citations' => (int) ($point['manual_citations'] ?? 0),
            ];
        }

        return $series;
    }
}
