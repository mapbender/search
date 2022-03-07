<?php

namespace Mapbender\SearchBundle\Entity;

/**
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class Style extends Base
{
    protected function getDefaults()
    {
        return parent::getDefaults() + array(
            'name' => null,
            'fillColor' => null,
            'fillOpacity' => 1.0,
            'strokeColor' => null,
            'strokeOpacity' => null,
            'strokeWidth' => 2,
            'strokeLinecap' => 'round',
            'strokeDashstyle' => 'solid',
            'pointRadius' => null,
            'label' => null,
            'fontColor' => null,
            'fontOpacity' => 1.0,
            'fontFamily' => null,
            'fontSize' => 11,
            'fontWeight' => 'regular',
        );
    }
}
