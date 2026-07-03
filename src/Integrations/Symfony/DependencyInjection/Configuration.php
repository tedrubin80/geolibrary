<?php

declare(strict_types=1);

namespace GEOOptimizer\Integrations\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('geo_optimizer');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('cache_enabled')->defaultFalse()->end()
                ->integerNode('cache_ttl')->defaultValue(3600)->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('adapter')->defaultValue('file')->end()
                        ->scalarNode('path')->defaultValue('%kernel.cache_dir%/geo')->end()
                        ->scalarNode('prefix')->defaultValue('geo_')->end()
                    ->end()
                ->end()
                ->arrayNode('analysis')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('min_word_count')->defaultValue(300)->end()
                        ->booleanNode('enable_readability')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
