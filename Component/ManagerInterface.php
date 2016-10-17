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
    /**
     * Saves the entity
     *
     * @param UniqueBaseEntity|BaseEntity $args
     * @param int                         $scope
     * @param int                         $parentId
     * @return UniqueBaseEntity|BaseEntity
     */
    public function save($args, $scope = null, $parentId = null);

    /**
     * @param        $id
     * @return UniqueBaseEntity|BaseEntity
     */
    public function getById($id);

    /**
     * Returns the entity
     *
     * @param array $args
     * @return UniqueBaseEntity|BaseEntity
     */
    public function create($args);

}