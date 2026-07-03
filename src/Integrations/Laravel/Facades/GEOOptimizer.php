<?php

declare(strict_types=1);

namespace GEOOptimizer\Integrations\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, mixed> optimize(array<string, mixed> $data)
 * @method static string generateLLMSTxt(array<string, mixed> $businessData)
 * @method static array<string, mixed> analyzeContent(string $content, array<string, mixed> $options = [])
 * @method static array<string, mixed> generateSchema(string $type, array<string, mixed> $data)
 * @method static string generateStructuredData(array<string, mixed> $businessData)
 * @method static array<int, string> getAvailableIndustries()
 *
 * @see \GEOOptimizer\GEOOptimizer
 */
class GEOOptimizer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \GEOOptimizer\GEOOptimizer::class;
    }
}
