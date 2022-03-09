<?php
namespace Mapbender\SearchBundle\Component;

use Mapbender\DataSourceBundle\Component\FeatureType;
use Mapbender\SearchBundle\Entity\Query;

/**
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 *
 * @method Query getById(integer $id)
 * @method Query[] getAll()
 * @method Query save(Query $entity)
  */
class QueryManager extends BaseManager
{
    /**
     * @param mixed[] $data
     * @return Query
     */
    public function create(array $data)
    {
        return new Query($data);
    }
}
