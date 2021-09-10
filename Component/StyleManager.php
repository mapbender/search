<?php
namespace Mapbender\SearchBundle\Component;

use Mapbender\SearchBundle\Entity\Style;

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
