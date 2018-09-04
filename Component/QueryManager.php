<?php
namespace Mapbender\SearchBundle\Component;

use Mapbender\DataSourceBundle\Component\FeatureType;
use Mapbender\DataSourceBundle\Component\FeatureTypeService;
use Mapbender\DataSourceBundle\Entity\Feature;
use Mapbender\SearchBundle\Entity\Query;
use Mapbender\SearchBundle\Entity\QueryCondition;
use Mapbender\SearchBundle\Entity\QuerySchema;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QueryManager
 *
 * @package Mapbender\SearchBundle\Component
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class QueryManager extends BaseManager
{
    /** @var QuerySchema[] Schemas */
    protected $schemas;
    /** @var FeatureTypeService */
    protected $featureTypeService;

    /**
     * QueryManager constructor.
     *
     * @param ContainerInterface $container
     * @param FeatureTypeService $featureTypeService
     * @param string $sqlitePath
     */
    public function __construct(ContainerInterface $container, FeatureTypeService $featureTypeService, $sqlitePath)
    {
        $this->featureTypeService = $featureTypeService;
        parent::__construct($container, $sqlitePath);

    }

    /**
     * Save query
     *
     * @param array $array
     * @return Query
     */
    public function saveArray($array)
    {
        $query = $this->create($array);
        return $this->save($query);
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
     * Save query
     *
     * @param Query $query
     * @param null  $scope
     * @param null  $parentId
     * @return Query
     */
    public function save($query, $scope = null, $parentId = null)
    {
        $queries        = $this->listQueries();
        $id             = $query->getId();
        $queries[ $id ] = $query;
        $result         = $this->db->saveData($this->tableName, $queries, $scope, $parentId, $this->getUserId());

        return $query; //isset($result[ $id ]) ? $result[ $query->getId() ] : null;

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
        $featureType     = $this->getQueryFeatureType($query);
        $queryConditions = $query->getConditions();
        $criteria        = array('where' => $this->buildCriteria($queryConditions, $featureType));
        array_merge($results, $featureType->search($criteria));

    }

    /**
     * @param QueryCondition[] $queryConditions
     * @param FeatureType      $featureType
     * @return string SQL
     */
    private function buildCriteria(array $queryConditions, FeatureType $featureType)
    {
        $connection      = $featureType->getConnection();
        $whereConditions = array();
        foreach ($queryConditions as $condition) {
            if (is_array($condition)) {
                $condition = new QueryCondition($condition);
            }
            $whereConditions[] = $connection->quoteIdentifier($condition->getFieldName())
                . ' ' . $condition->getOperator()
                . ' ' . $connection->quote($condition->getValue());
        }
        return implode(' AND ', $whereConditions);
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
     * @param Query       $query
     * @param null|string $intersectGeometry
     * @param null        $srid
     * @return array
     */
    public function check(Query $query, $intersectGeometry = null, $srid = null)
    {
        $featureType = $this->getQueryFeatureType($query);
        $connection  = $featureType->getConnection();
        $driver      = $featureType->getDriver();
        $sql         = $this->buildSql($query, true);

        if ($query->isExtendOnly()) {
            $intersectGeometry = $driver::roundGeometry($intersectGeometry, 2);
            $sql .= ' AND ' . $driver->getIntersectCondition($intersectGeometry, $featureType->getGeomField(), $srid, $featureType->getSrid());
        }

        $runningTime = (microtime(true) * 1000);
        $count       = $connection->fetchColumn($sql);
        $runningTime = (microtime(true) * 1000) - $runningTime;
        $explainInfo = array();


        foreach ($connection->fetchAll("EXPLAIN " . $sql) as $info) {
            $explainInfo[] = current($info);
        }

        $result = array(
            'count'         => $count,
            'explainInfo'   => $explainInfo,
            'executionTime' => round($runningTime) . 'ms',
        );

        return $result;
    }

    /**
     * @param $id
     * @return \Eslider\Entity\HKV
     */
    public function removeById($id)
    {
        $queries = $this->listQueries();
        if (isset($queries[ $id ])) {
            unset($queries[ $id ]);
        }
        return $this->db->saveData($this->tableName, $queries, null, null, $this->getUserId());
    }

    /**
     * @param Query  $query
     * @param bool   $count Count queries
     * @param null   $fieldNames
     * @param string $tableAliasName
     * @return array
     */
    public function buildSql(Query $query, $count = false, $fieldNames = null, $tableAliasName = 't')
    {
        $featureType = $this->getQueryFeatureType($query);
        $connection  = $featureType->getConnection();
        $fields      = array();

        if ($count) {
            $fields[] = 'count(*)';
        } else {
            $fieldNames = $fieldNames ? $fieldNames : $featureType->getFields();
            foreach ($fieldNames as $fieldName) {
                $fields[] = $connection->quoteIdentifier($fieldName);
            }
        }

        $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $connection->quoteIdentifier($featureType->getTableName()) . ' ' . $tableAliasName . ' WHERE 1=1 ';

        if ($query->hasConditions()) {
            $sql .= ' AND ' . $this->buildCriteria($query->getConditions(), $featureType);
        }

        return $sql;
    }

    /**
     * Execute and fetch query results
     *
     * @param Query $query
     * @param array $args
     * @return array
     */
    public function fetchQuery(Query $query, array $args = array())
    {
        $featureType = $this->getQueryFeatureType($query);
        return $featureType->search(array_merge(array(
            'where'      => $this->buildCriteria($query->getConditions(), $featureType),
            'returnType' => 'FeatureCollection'
        ), $args));
    }

    /**
     * Set query schemas
     *
     * @param array $schemas
     * @return QueryManager
     */
    public function setSchemas(array $schemas)
    {
        $list = array();

        foreach ($schemas as $schemaArgs) {
            if (!($schemaArgs instanceof QuerySchema)) {
                $list[] = new QuerySchema($schemaArgs);
            } else {
                $list[] = $schemaArgs;
            }
        }

        $this->schemas = $list;
        return $this;
    }

    /**
     * @return QuerySchema[]
     */
    public function getSchemas()
    {
        return $this->schemas;
    }

    /**
     * @param $id
     * @return QuerySchema
     */
    public function getSchemaById($id)
    {
        return $this->schemas[ $id ];
    }

    /**
     * Get query feature type service
     *
     * @param Query $query
     * @return FeatureType
     */
    public function getQueryFeatureType(Query $query)
    {
        return $this->featureTypeService
            ->get($this
                ->getSchemaById($query->getSchemaId())
                ->getFeatureType()
            );
    }
}