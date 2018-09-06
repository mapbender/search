<?php
namespace Mapbender\SearchBundle\Component;

use Mapbender\SearchBundle\Entity\Style;

/**
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 *
 * @method Style getById(integer $id, $userId=null)
 * @method Style[] getAll($userId)
 * @method Style save(Style $entity, $userId)
 * @method Style createFiltered(array $data)
 */
class StyleManager extends BaseManager
{
    /**
     * StyleManager constructor.
     *
     * @param string $sqlitePath
     */
    public function __construct($sqlitePath)
    {
        parent::__construct($sqlitePath);
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
