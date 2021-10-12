<?php
namespace Mapbender\SearchBundle\Component;

use Mapbender\SearchBundle\Entity\StyleMap;

/**
 *
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 *
 * @method StyleMap getById(integer $id)
 * @method StyleMap[] getAll()
 * @method StyleMap save(StyleMap $entity)
 */
class StyleMapManager extends BaseManager
{
    /**
     * @param mixed[] $data
     * @return StyleMap
     */
    public function create(array $data)
    {
        return new StyleMap($data);
    }
}
