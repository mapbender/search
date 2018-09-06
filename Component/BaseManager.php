<?php

namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
use Mapbender\SearchBundle\Entity\UniqueBase;

/**
 * Class BaseManager
 *
 * @package Mapbender\SearchBundle\Component
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
abstract class BaseManager implements ManagerInterface
{
    /** @var HKVStorage */
    protected $db;

    /** @var string tableName */
    protected $tableName;

    /** @var string path */
    protected $path;

    /**
     * @param string $path full filename for sqlite table
     */
    public function __construct($path)
    {
        $this->path      = $path;
        $baseName = preg_replace('#^([^/]*/)*#', '', $this->path);
        $this->tableName = preg_replace('#\..*$#', '', $baseName);
        $this->createDB();
    }

    /**
     * @param mixed[] $data
     * @return UniqueBase
     */
    abstract public function create($data);

    /**
     * List all records
     *
     * @param string $userId
     * @return UniqueBase[]
     * @internal param int $id
     */
    public function getAll($userId)
    {
        /** @var UniqueBase[] $list */
        $list = $this->db->getData($this->tableName, null, null, $userId);
        return $list ? $list : array();
    }

    /**
     * @param string $userId
     * @param int $id
     * @return UniqueBase|null
     */
    public function getById($id, $userId = null)
    {
        $allRecords = $this->getAll($userId);
        return isset($allRecords[$id]) ? $allRecords[$id] : null;

    }

    /**
     * @param string $userId
     * @param UniqueBase $entity
     * @return UniqueBase
     */
    public function save($entity, $userId)
    {
        if (!$entity->getId()) {
            $entity->setId($this->generateUUID());
        }
        $all = $this->getAll($userId);
        $id = $entity->getId();
        $all[$id] = $entity;
        $this->db->saveData($this->tableName, $all, null, null, $userId);

        return $entity;
    }

    /**
     * @param string $userId
     * @param string $id
     * @return bool
     */
    public function remove($id, $userId)
    {
        $list = $this->getAll($userId);
        $wasMissed = isset($list[$id]);
        unset($list[$id]);

        $this->db->saveData($this->tableName, $list, null, null, $userId);

        return $wasMissed;
    }

    /**
     * @param $data
     * @return UniqueBase
     */
    public function createFiltered($data)
    {
        $filtered = $this->filterFields($data);
        return $this->create($filtered);
    }

    /**
     * @return string[] field names that are not settable by the user
     */
    protected function getBlacklistedFields()
    {
        return array(
        );
    }

    /**
     * Remove blacklisted fields
     *
     * @param mixed[] $data
     * @return mixed[]
     */
    protected function filterFields($data)
    {
        foreach ($this->getBlacklistedFields() as $deniedFieldName) {
            unset($data[$deniedFieldName]);
        }
        return $data;
    }


    /**
     * Drop database
     *
     * @return bool
     */
    public function dropDatabase()
    {
        return $this->db->destroy();
    }


    /**
     * @return string
     */
    protected function generateUUID()
    {
        return uniqid("", true);
    }

    /**
     */
    public function createDB()
    {
        $this->db = new HKVStorage($this->path, $this->tableName);
    }
}