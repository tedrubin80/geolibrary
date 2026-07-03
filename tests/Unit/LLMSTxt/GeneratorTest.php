<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\LLMSTxt;

use GEOOptimizer\Exceptions\ValidationException;
use GEOOptimizer\LLMSTxt\Generator;
use PHPUnit\Framework\TestCase;

class GeneratorTest extends TestCase
{
    private Generator $generator;

    private array $sampleBusinessData;

    protected function setUp(): void
    {
        $this->generator = new Generator([
            'templates_path' => dirname(__DIR__, 3) . '/src/LLMSTxt/Templates',
        ]);

        $this->sampleBusinessData = [
            'business_name' => 'Acme Coffee',
            'industry' => 'restaurant',
            'description' => 'Specialty coffee roaster and cafe.',
            'location' => 'San Francisco, CA',
            'phone' => '555-0100',
            'email' => 'hello@acme.test',
            'website' => 'https://acme.test',
            'services' => ['Espresso', 'Pour Over'],
            'hours' => [
                'Monday' => '9:00 AM - 5:00 PM',
            ],
        ];
    }

    public function testGenerateIncludesBusinessDetails(): void
    {
        $content = $this->generator->generate($this->sampleBusinessData);

        $this->assertStringContainsString('Acme Coffee', $content);
        $this->assertStringContainsString('Specialty coffee roaster and cafe.', $content);
        $this->assertStringContainsString('555-0100', $content);
    }

    public function testGenerateRequiresDescription(): void
    {
        $this->expectException(ValidationException::class);

        $this->generator->generate([
            'business_name' => 'Acme Coffee',
        ]);
    }

    public function testGetAvailableTemplatesIncludesBusinessTemplate(): void
    {
        $templates = $this->generator->getAvailableTemplates();

        $this->assertContains('business', $templates);
    }
}
