<?php
namespace Mapbender\SearchBundle;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Mapbender search bundle
 *
 * @author Andriy Oblivantsev
 */
class MapbenderSearchBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('services.xml');
        $loader->load('elements.xml');
    }

    public function getContainerExtension()
    {
        return null;
    }
}
