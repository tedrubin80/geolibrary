<?php

declare(strict_types=1);

namespace GEOOptimizer\Integrations\Symfony\DependencyInjection;

use GEOOptimizer\GEOOptimizer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class GEOOptimizerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('geo_optimizer.config', $config);

        $container->register(GEOOptimizer::class, GEOOptimizer::class)
            ->setPublic(true)
            ->setFactory([GEOOptimizerFactory::class, 'create'])
            ->setArguments(['%geo_optimizer.config%']);
    }

    public function getAlias(): string
    {
        return 'geo_optimizer';
    }
}
