<?php
namespace Mapbender\SearchBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\Component\SecurityContext;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

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
