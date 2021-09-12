<?php


namespace Mapbender\SearchBundle\Component;


use Mapbender\DataSourceBundle\Component\FeatureType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Only used on data-source < 0.1.22. Automatically displaced by upstream service on data-source >= 0.1.22
 * for compatibility with Symfony >= 3.
 *
 * @see \Mapbender\SearchBundle\DependencyInjection\Compiler\FeatureTypeFactoryProviderPass
 */
class FeatureTypeFactory
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function createFeatureType(array $config)
    {
        return new FeatureType($this->container, $config);
    }
}
