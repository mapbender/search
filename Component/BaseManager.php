<?php

namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
use Mapbender\CoreBundle\Component\SecurityContext;
use Monolog\Logger;
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

    /** @var Logger */
    protected $logger;

    /** @var ContainerInterface */
    protected $container;

    /**
     * BaseManager constructor.
     *
     * @param ContainerInterface $container
     * @param string             $tableName
     */
    public function __construct(ContainerInterface $container = null, $tableName = "default")
    {
        $this->container = $container;
        $kernel          = $this->container->get('kernel');
        $this->path      = $kernel->getRootDir() . "/config/" . $tableName . ".sqlite";
        $this->tableName = $tableName;
        $this->createDB();
        $this->logger = $this->container->get('logger');
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