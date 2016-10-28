<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
use Mapbender\DataSourceBundle\Component\FeatureType;
use Mapbender\DataSourceBundle\Component\FeatureTypeService;
use Mapbender\DataSourceBundle\Entity\Feature;
use Mapbender\SearchBundle\Entity\Query;
use Mapbender\SearchBundle\Entity\QueryCondition;
use Symfony\Component\Config\Definition\Exception\Exception;
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
     * @param ContainerInterface|null $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        parent::__construct($container, "queries");
    }


    /**
     * Save query
     *
     * @param array $array
     * @param null  $scope
     * @param null  $parentId
     * @return Query
     */
    public function saveArray($array, $scope = null, $parentId = null)
    {
        $query = $this->create($array);
        return $this->save($query, $scope, $parentId);
    }

    /**
     * Save query
     *
     * @param Query $query
     * @param null  $scope
     * @param null  $parentId
     * @return Query
     */
    public function save($query, $scope = null, $parentId = null)
    {
        $queries = $this->listQueries();
        $id      = $query->getId();

        if ($queries == null) {
            $queries = array();
        }

        $queries[ $id ] = $query;
        $result         = $this->db->saveData($this->tableName, $queries, $scope, $parentId, $this->getUserId());

        $children = $result->getChildren();

        foreach ($children as $key => $child) {
            if ($child->getKey() == $id) {
                return $child;
            }
        }

        return isset($result[ $id ]) ? $result[ $query->getId() ] : null;

    }


    /**
     * Get query by id
     *
     * @param int $id
     * @return Query|null
     * @internal param string $userId
     */
    public function getById($id)
    {
        $queries = $this->listQueries();
        return isset($queries[ $id ]) ? $queries[ $id ] : null;
    }


    /**
     * List all queries
     *
     * @return Query[]
     * @internal param int $id
     */
    public function listQueries()
    {
        $list = $this->db->getData($this->tableName, null, null, $this->getUserId());
        return $list ? $list : array();
    }


    /**
     * Returns all
     *
     * @param FeatureTypeService $featureService
     * @return Feature[]
     */
    public function listQueriesByAllFeatureTypes($featureService = null)
    {
        return $this->listQueriesByFeatureTypes($featureService, $featureService->getFeatureTypeDeclarations());
    }


    /**
     * @param FeatureTypeService $featureService
     * @param array              $featureTypes
     * @return Feature[]
     */
    public function listQueriesByFeatureTypes($featureService, array $featureTypes)
    {
        $queries = $this->listQueries();
        $results = array();

        foreach ($featureTypes as $i => $feature) {
            foreach ($queries as $j => $query) {
                $this->addFeatureType($results, $featureService, $feature, $query);
            }

        }

        return $results;
    }


    /**
     * @param array              $results
     * @param FeatureTypeService $featureService
     * @param string             $feature
     * @param Query              $query
     */
    private function addFeatureType(&$results, $featureService, $feature, $query)
    {
        if ($feature == $query->getFeatureType()) {

            $featureType = $featureService->get($feature);
            if ($featureType == null) {
                throw new Exception("You have to define the feature type in the corresponding config yml!");
            }
            $queryConditions = $query->getConditions();
            $criteria        = $this->buildCriteria($queryConditions, $featureType);
            array_merge($results, $featureType->search($criteria));

        }
    }

    /**
     * @param QueryCondition[] $queryConditions
     * @param FeatureType      $featureType
     * @return array
     */
    private function buildCriteria($queryConditions, $featureType)
    {
        $queryParts = array();
        $escaper    = $featureType->getConnection();
        foreach ($queryConditions as $k => $condition) {
            $queryParts[] = $escaper->quoteIdentifier($condition->getFieldName())
                . " " . $condition->getOperator()
                . " " . $escaper->quote($condition->getValue())
                . " ";

        }
        return array("where" => implode(" AND ", $queryParts));
    }

    /**
     * @param $args
     * @return Query
     */
    public function create($args)
    {
        $query = new Query($args);
        if (!isset($args["id"])) {
            $query->setId($this->generateUUID());
        }
        $query->setUserId($this->getUserId());
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