<?php
namespace Mapbender\SearchBundle\Component;

use Mapbender\SearchBundle\Entity\Style;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 *
 * @method Style getById(integer $id)
 * @method Style[] getAll()
 * @method Style save(Style $entity)
 * @method Style createFiltered(array $data)
 */
class StyleManager extends BaseManager
{
    /**
     * StyleManager constructor.
     *
     * @param ContainerInterface|null $container
     * @param string $sqlitePath
     */
    public function __construct(ContainerInterface $container, $sqlitePath)
    {
        parent::__construct($container, $sqlitePath);
    }

    /**
     * Create style object
     *
     * @param $args
     * @return Style
     */
    public function create($args)
    {
        return new Style($args);
    }

    protected function getBlacklistedFields()
    {
        return array_merge(parent::getBlacklistedFields(), array(
            'styleMaps',
            'pointerEvents',
        ));
    }
}
