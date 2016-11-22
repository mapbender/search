<?php

namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
use Mapbender\ConfiguratorBundle\Component\BaseComponent;
use Mapbender\CoreBundle\Component\SecurityContext;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BaseManager
 *
 * @package Mapbender\SearchBundle\Component
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
abstract class BaseManager extends BaseComponent implements ManagerInterface
{
    /** @var HKVStorage */
    protected $db;

    /* @var Configuration configuration */
    protected $configuration;

    /* @var string userId */
    protected $userId = SecurityContext::USER_ANONYMOUS_ID;

    /** @var string tableName */
    protected $tableName;

    /** @var string path */
    protected $path;

    /** @var Logger */
    protected $logger;

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

        parent::__construct($container);
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
     * @return configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param configuration $configuration
     * @return BaseManager
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
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