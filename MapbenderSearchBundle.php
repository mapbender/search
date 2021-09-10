<?php
namespace Mapbender\SearchBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Mapbender search bundle
 *
 * @author Andriy Oblivantsev
 */
class MapbenderSearchBundle extends MapbenderBundle
{
    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\SearchBundle\Element\Search'
        );
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
}
