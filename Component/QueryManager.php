<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
use Eslider\Entity\HKV;
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
    /** @var HKVStorage $db */
    protected $db;


    /** @var Configuration $configuration */
    protected $configuration;


    const SERVICE_NAME = "mapbender.query.manager";


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
     * Save query
     *
     * @param array $array
     * @return HKV
     */
    public function saveArray($array, $scope = null, $parentId = null)
    {
        $query = $this->create($array);
        $HKV   = $this->save($query, $scope, $parentId);
        return $HKV;
    }

    /**
     * Save query
     *
     * @param Query $query
     * @return HKV
     */
    public function save($query, $scope = null, $parentId = null)
    {
        $queries = $this->listQueries();

        if ($queries == null) {
            $queries = array();
        }

        $queries[ $query->getId() ] = $query;
        $result                     = $this->db->saveData($this->tableName, $queries, $scope, $parentId, $this->getUserId());

        $children = $result->getChildren();

        foreach ($children as $key => $child) {
            if ($child->getKey() == $query->getId()) {
                return $child;
            }
        }

        return null;

    }


    /**
     * Get query by id
     *
     * @param int    $id
     * @param string $userId
     * @return HKV|null
     */
    public function getById($id)
    {
        $queries = $this->listQueries();
        return isset($queries[ $id ]) ? $queries[ $id ] : null;
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


}