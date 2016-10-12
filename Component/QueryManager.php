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
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class QueryManager extends BaseManager
{
    /** @var HKVStorage $db **/
    protected $db;


    /** @var Configuration $configuration **/
    protected $configuration;

    /** @var int $userId **/
    protected $userId;


    const SERVICE_NAME = "mapbender.query.manager";

    /** @var boolean $isPublic **/
    private $public;

    /**
     * QueryManager constructor.
     *
     * @param ContainerInterface
     * $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        parent::__construct($container, "queries");
    }


    /**
     * save query
     *
     * @param Query $query
     * @return HKV
     */
    public function save(Query $query, $scope = null, $parentId = null)
    {
        $list   = $this->listQueries();
        $list[] = $query;
        $userId = $query->getUserId();
        return $this->db->saveData($this->tableName, $list, $scope, $parentId, $userId);
    }


    /**
     * Get query by id
     *
     * @param int $id
     * @return HKV|null
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
     * @return HKV[]
     */
    public function listQueries()
    {
        return $this->db->getData($this->tableName, null, null, $this->getUserId());
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
        $this->db->saveData($this->tableName, $queries);
        return $found;
    }

    /**
     * @param $args
     * @return Query
     */
    public function create($args)
    {
        $query = new Query($args);
        $query->setId($this->generateUUID());
        $query->setUserId($this->getUserId());
        $query->setConnectionName(isset($this->configuration) ? $this->configuration->getConnection() : Configuration::DEFAULT_CONNECTION);
        return $query;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param Configuration $configuration
     * @return QueryManager
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @param int $userId
     * @return QueryManager
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
     * @param boolean $public
     * @return QueryManager
     */
    public function setPublic($public)
    {
        $this->public = $public;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublic()
    {
        return $this->public;
    }


}