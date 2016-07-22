<?php

namespace Mapbender\SearchBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Adds test applications
 */
class MapbenderSearchExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        //$loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        //$loader->load('services.xml');
        $yamlFileLoader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $yamlFileLoader->load('applications.yml');
    }

    public function getAlias()
    {
        return 'mapbender_search';
    }
}
