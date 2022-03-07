<?php
namespace Mapbender\SearchBundle\Component;

use Mapbender\SearchBundle\Entity\Style;

/**
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 *
 * @method Style getById(integer $id)
 * @method Style[] getAll()
 * @method Style save(Style $entity)
 */
class StyleManager extends BaseManager
{

    /**
     * Create style object
     *
     * @param mixed[] $data
     * @return Style
     */
    public function create(array $data)
    {
        return new Style($data);
    }
}
