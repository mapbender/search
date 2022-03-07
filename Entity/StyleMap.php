<?php

namespace Mapbender\SearchBundle\Entity;

/**
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class StyleMap extends Base
{
    protected function getDefaults()
    {
        return parent::getDefaults() + array(
            'name' => null,
            'styles' => array(),
        );
    }
}
