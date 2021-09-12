<?php

namespace Mapbender\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FeatureTypeFactoryProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('mbds.default_featuretype_factory')) {
            $container->removeDefinition('mapbender.search.featuretype_factory');
            $container->setAlias('mapbender.search.featuretype_factory', 'mbds.default_featuretype_factory');
        }
    }
}
