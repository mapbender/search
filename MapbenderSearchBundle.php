<?php
namespace Mapbender\QuerySearchBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;

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
}
