<?php

declare(strict_types=1);

namespace GEOOptimizer\Integrations\Laravel;

use GEOOptimizer\GEOOptimizer;
use Illuminate\Support\ServiceProvider;

class GEOOptimizerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/geooptimizer.php', 'geooptimizer');

        $this->app->singleton(GEOOptimizer::class, function ($app) {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('geooptimizer', []);

            return new GEOOptimizer($config);
        });

        $this->app->alias(GEOOptimizer::class, 'geooptimizer');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/geooptimizer.php' => config_path('geooptimizer.php'),
            ], 'geooptimizer-config');
        }
    }

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [GEOOptimizer::class, 'geooptimizer'];
    }
}
