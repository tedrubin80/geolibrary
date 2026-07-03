<?php

declare(strict_types=1);

namespace GEOOptimizer\Integrations\Symfony\DependencyInjection;

use GEOOptimizer\GEOOptimizer;

final class GEOOptimizerFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config): GEOOptimizer
    {
        return new GEOOptimizer($config);
    }
}
