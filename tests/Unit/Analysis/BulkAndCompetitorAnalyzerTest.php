<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\Analysis;

use GEOOptimizer\Analysis\BulkSiteAnalyzer;
use GEOOptimizer\Analysis\CompetitorAnalyzer;
use GEOOptimizer\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class BulkAndCompetitorAnalyzerTest extends TestCase
{
    private string $sampleContent;

    protected function setUp(): void
    {
        $this->sampleContent = 'Acme Coffee is a certified specialty roaster with professional baristas serving espresso and pour over coffee in San Francisco.';
    }

    public function testBulkAnalyzeRanksItemsByScore(): void
    {
        $analyzer = new BulkSiteAnalyzer();
        $result = $analyzer->analyze([
            ['id' => 'short', 'content' => 'Brief text.'],
            ['id' => 'long', 'content' => $this->sampleContent . ' ' . str_repeat('Fresh beans and expert brewing. ', 5)],
        ]);

        $this->assertSame(2, $result['count']);
        $this->assertSame('long', $result['ranking'][0]);
    }

    public function testBulkAnalyzeRequiresItems(): void
    {
        $this->expectException(ValidationException::class);
        (new BulkSiteAnalyzer())->analyze([]);
    }

    public function testCompareIdentifiesLeader(): void
    {
        $analyzer = new CompetitorAnalyzer();
        $result = $analyzer->compare(
            'Acme Coffee',
            $this->sampleContent,
            [
                ['name' => 'Rival Roasters', 'content' => 'Coffee shop.'],
            ]
        );

        $this->assertSame('Acme Coffee', $result['primary']['name']);
        $this->assertCount(2, $result['entries']);
    }

    public function testCompareRequiresCompetitors(): void
    {
        $this->expectException(ValidationException::class);
        (new CompetitorAnalyzer())->compare('Acme Coffee', $this->sampleContent, []);
    }
}
