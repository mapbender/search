<?php


namespace Mapbender\SearchBundle\Component;

/**
 * Emulates legacy FeatureTypeService (gone in data-source >= 0.2.0).
 * Only emulates functionality / public API used here
 */
class FeatureTypeRegistry
{
    /** @see FeatureTypeFactoryProviderPass */
    /** @var FeatureTypeFactory|\Mapbender\DataSourceBundle\Component\Factory\FeatureTypeFactory */
    protected $factory;
    /** @var array[] */
    protected $definitions;
    protected $instances = array();

    public function __construct($factory, array $definitions)
    {
        $this->factory = $factory;
        $this->definitions = $definitions;
    }

    public function get($name)
    {
        if (!\array_key_exists($name, $this->instances)) {
            $this->instances[$name] = $this->factory->createFeatureType($this->definitions[$name]);
        }
        return $this->instances[$name];
    }

    public function getFeatureTypeDeclarations()
    {
        return $this->definitions;
    }
}
