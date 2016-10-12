<?php

namespace Mapbender\SearchBundle\Component;

use Eslider\Entity\BaseEntity;
use Eslider\Entity\UniqueBaseEntity;
use Mapbender\CoreBundle\Component\SecurityContext;

/**
 * Interface ManagerInterface
 *
 * @package Mapbender\SearchBundle\Component
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
interface ManagerInterface
{
    public function save();

    /**
     * @param        $id
     * @param string $userId
     * @return UniqueBaseEntity|BaseEntity
     */
    public function getById($id, $userId = SecurityContext::USER_ANONYMOUS_ID);

    /**
     * Remove query with certain $queryId
     *
     * @param string|int $queryId
     * @return bool
     */
    public function remove($queryId);

    /**
     * Returns the entity
     *
     * @param array $args
     * @return UniqueBaseEntity|BaseEntity
     */
    public function create($args);

}