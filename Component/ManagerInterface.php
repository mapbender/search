<?php

namespace Mapbender\SearchBundle\Component;

use Eslider\Entity\BaseEntity;
use Eslider\Entity\UniqueBaseEntity;

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
     * @return UniqueBaseEntity|BaseEntity
     */
    public function save($args);

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