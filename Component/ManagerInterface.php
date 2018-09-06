<?php

namespace Mapbender\SearchBundle\Component;

use Mapbender\SearchBundle\Entity\UniqueBase;

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
     * @param UniqueBase $args
     * @param string $userId
     * @return UniqueBase
     */
    public function save($args, $userId);

    /**
     * @param        $id
     * @param string $userId
     * @return UniqueBase
     */
    public function getById($id, $userId=null);

    /**
     * Returns the entity
     *
     * @param array $args
     * @return UniqueBase
     */
    public function create($args);
}
