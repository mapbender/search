<?php

namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
use Mapbender\CoreBundle\Component\SecurityContext;
use Mapbender\SearchBundle\Entity\UniqueBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    /* @var string userId */
    protected $userId = SecurityContext::USER_ANONYMOUS_ID;

    /** @var string tableName */
    protected $tableName;

    /** @var string path */
    protected $path;

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     * @param string $path full filename for sqlite table
     */
    public function __construct(ContainerInterface $container, $path)
    {
        $this->container = $container;
        $this->path      = $path;
        $baseName = preg_replace('#^([^/]*/)*#', '', $this->path);
        $this->tableName = preg_replace('#\..*$#', '', $baseName);
        $this->createDB();
        $this->setUserId($container->get("security.context")->getUser()->getId());
    }

    /**
     * @param mixed[] $data
     * @return UniqueBase
     */
    abstract public function create($data);

    /**
     * List all records
     *
     * @return UniqueBase[]
     * @internal param int $id
     */
    public function getAll()
    {
        /** @var UniqueBase[] $list */
        $list = $this->db->getData($this->tableName, null, null, $this->getUserId());
        return $list ? $list : array();
    }

    /**
     * @param int $id
     * @return UniqueBase|null
     */
    public function getById($id)
    {
        $allRecords = $this->getAll();
        return isset($allRecords[$id]) ? $allRecords[$id] : null;

    }

    /**
     * @param UniqueBase $entity
     * @return UniqueBase
     */
    public function save($entity)
    {
        if (!$entity->getId()) {
            $entity->setId($this->generateUUID());
        }
        $all = $this->getAll();
        $id = $entity->getId();
        $all[$id] = $entity;
        $this->db->saveData($this->tableName, $all, null, null, $this->getUserId());

        return $entity;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function remove($id)
    {
        $list = $this->getAll();
        $wasMissed = isset($list[$id]);
        unset($list[$id]);

        $this->db->saveData($this->tableName, $list, null, null, $this->getUserId());

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
            'id',
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
     * @param int $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     */
    public function createDB()
    {
        $this->db = new HKVStorage($this->path, $this->tableName);
    }
}