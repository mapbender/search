<?php

namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
use Mapbender\CoreBundle\Component\SecurityContext;
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