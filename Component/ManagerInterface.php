<?php

namespace Mapbender\SearchBundle\Component;

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
     * @param UniqueBaseEntity $args
     * @return UniqueBaseEntity
     */
    public function save($args);

    /**
     * @param        $id
     * @return UniqueBaseEntity
     */
    public function getById($id);

    /**
     * Returns the entity
     *
     * @param array $args
     * @return UniqueBaseEntity
     */
    public function create($args);
}
