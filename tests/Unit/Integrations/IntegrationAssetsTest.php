<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\Integrations;

use PHPUnit\Framework\TestCase;

class IntegrationAssetsTest extends TestCase
{
    public function testLaravelServiceProviderFileExists(): void
    {
        $this->assertFileExists(
            dirname(__DIR__, 3) . '/src/Integrations/Laravel/GEOOptimizerServiceProvider.php'
        );
    }

    public function testSymfonyBundleFileExists(): void
    {
        $this->assertFileExists(
            dirname(__DIR__, 3) . '/src/Integrations/Symfony/GEOOptimizerBundle.php'
        );
        $this->assertFileExists(
            dirname(__DIR__, 3) . '/src/Integrations/Symfony/Resources/config/services.xml'
        );
    }

    public function testApiRouterExists(): void
    {
        $this->assertFileExists(dirname(__DIR__, 3) . '/public/api/index.php');
        $this->assertFileExists(dirname(__DIR__, 3) . '/public/router.php');
        $this->assertFileExists(dirname(__DIR__, 3) . '/bin/geo-api');
        $this->assertFileExists(dirname(__DIR__, 3) . '/Dockerfile');
        $this->assertFileExists(dirname(__DIR__, 3) . '/railway.toml');
    }
}
