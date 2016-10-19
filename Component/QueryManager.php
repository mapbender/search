<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
use Eslider\Entity\HKV;
use Mapbender\DataSourceBundle\Component\FeatureType;
use Mapbender\DataSourceBundle\Component\FeatureTypeService;
use Mapbender\DataSourceBundle\Entity\Feature;
use Mapbender\SearchBundle\Entity\Query;
use Mapbender\SearchBundle\Entity\QueryCondition;
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
     * @return \Mapbender\SearchBundle\Entity\Query[]
     * @internal param int $id
     */
    public function listQueries()
    {
        return $this->db->getData($this->tableName, null, null, $this->getUserId());
    }

    /**
     * Returns all
     *
     * @param FeatureTypeService $featureService
     * @return Feature[]
     */
    public function listQueriesByFeatureType($featureService = null)
    {
        $queries = $this->listQueries();
        $results = array();
        if ($featureService != null) {
            foreach ($featureService->getFeatureTypeDeclarations() as $i => $feature) {
                foreach ($queries as $j => $query) {
                    $this->addFeatureType($results, $featureService, $feature, $query);
                }
            }
        }

        return $results;
    }


    /**
     * @param array              $results
     * @param FeatureTypeService $featureService
     * @param Feature            $feature
     * @param Query              $query
     */
    private function addFeatureType(&$results, $featureService, $feature, $query)
    {
        if ($feature->getType() == $query->getFeatureType()) {

            $featureType     = $featureService->get($feature->getType());
            $queryConditions = $query->getConditions();

            $criteria = $this->buildCriteria($queryConditions, $featureType);
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