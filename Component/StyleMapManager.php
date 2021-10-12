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
 * @method StyleMap createFiltered(array $data)
 */
class StyleMapManager extends BaseManager
{
    /**
     * @param array $args
     * @return StyleMap
     */
    public function create($args)
    {
        return new StyleMap($args);
    }
}
