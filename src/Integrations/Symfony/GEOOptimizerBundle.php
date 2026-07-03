<?php

declare(strict_types=1);

namespace GEOOptimizer\Integrations\Symfony;

use GEOOptimizer\Integrations\Symfony\DependencyInjection\GEOOptimizerExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GEOOptimizerBundle extends Bundle
{
    public function getContainerExtension(): GEOOptimizerExtension
    {
        return new GEOOptimizerExtension();
    }
}
