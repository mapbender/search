<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
use Eslider\Entity\HKV;
use Eslider\Entity\HKVSearchFilter;
use FOM\UserBundle\Entity\User;
use Mapbender\ConfiguratorBundle\Component\BaseComponent;
use Mapbender\ConfiguratorBundle\Component\Configurator;
use Mapbender\CoreBundle\Component\SecurityContext;
use Mapbender\SearchBundle\Entity\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QueryManager
 *
 * @package Mapbender\SearchBundle\Component
 */
class QueryManager extends BaseComponent
{
    /** @var HKVStorage */
    protected $db;


    /* @var Configuration configuration */
    protected $configuration;

    /* @var int userid */
    protected $userid;


    /**
     * configurator constructor.
     *
     * @param ContainerInterface
     * $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
        $kernel          = $this->container->get('kernel');
        $path            = $kernel->getRootDir() . "/config/queries.sqlite";
        $tableName       = "queries";
        $this->db        = new HKVStorage($path, $tableName);

        parent::__construct($container);
    }


    /**
     * save query
     *
     * @param query $query
     * @return hkv
     */
    public function save(query $query)
    {
        $list   = $this->listQueries();
        $list[] = $query;
        $userid = $query->getUserId();
        return $this->db->saveData("queries", $list, null, null, $userid);
    }


    /**
     * get query by id
     *
     * @param int $id
     * @return hkv|null
     */
    public function getById($id, $userId = null)
    {
        $list = $this->listQueries();

        foreach ($list as $key => $value) {
            if ($value->getId() == $id && $value->getUserId() == $userId) {
                return $value;
            }
        }

        return null;
    }


    /**
     * List all queries
     *
     * @param int $id
     * @return hkv[]
     */
    public function listQueries()
    {
        return $this->db->getData("queries", null, null, $this->getUserId());
    }

    /**
     * remove query with certain $queryid
     *
     * @param $queryid
     * @return bool
     */
    public function remove($queryid)
    {
        $found = false;

        $queries = $this->listQueries();
        foreach ($queries as $key => $query) {
            if ($query->getId() == $queryid) {
                unset($queries[ $key ]);
                $found = true;
            }
        }
        $this->db->saveData("queries", $queries);
        return $found;
    }


    /**
     * drop database
     *
     * @return bool
     */
    public function dropDatabase()
    {
        return $this->db->destroy();
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
     * @return querymanager
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @param int $userid
     * @return querymanager
     */
    public function setUserId($userid)
    {
        $this->userid = $userid;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userid;
    }

    /**
     * @param query $query
     */
    private function generateUUID($query)
    {
        $query->setId(uniqid("", true));
    }


    /**
     * @param $args
     * @return query
     */
    public function create($args)
    {
        $query = new Query($args);
        $this->generateUUID($query);
        $query->setUserId($this->getUserId());
        $query->setConnectionName(isset($this->configuration) ? $this->configuration->getConnection() : Configuration::DEFAULT_CONNECTION);
        return $query;
    }

}