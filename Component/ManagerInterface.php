<?php

namespace Mapbender\SearchBundle\Component;

use Eslider\Entity\BaseEntity;

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
     * @param BaseEntity $args
     * @return BaseEntity
     */
    public function save($args);

    /**
     * @param        $id
     * @return BaseEntity
     */
    public function getById($id);

    /**
     * Returns the entity
     *
     * @param array $args
     * @return BaseEntity
     */
    public function create($args);

}
