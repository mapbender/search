<?php
namespace Mapbender\SearchBundle\Component;

use Mapbender\DataSourceBundle\Component\FeatureType;
use Mapbender\DataSourceBundle\Component\FeatureTypeService;
use Mapbender\SearchBundle\Entity\Query;
use Mapbender\SearchBundle\Entity\QueryCondition;
use Mapbender\SearchBundle\Entity\QuerySchema;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 *
 * @method Query getById(integer $id)
 * @method Query[] getAll()
 * @method Query save(Query $entity)
 * @method Query createFiltered(array $data)
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
     * @param $args
     * @return Query
     */
    public function create($args)
    {
        return new Query($args);
    }

    protected function getBlacklistedFields()
    {
        return array_merge(parent::getBlacklistedFields(), array(
            'where',
        ));
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
     * @param Query  $query
     * @param bool   $count Count queries
     * @param null   $fieldNames
     * @param string $tableAliasName
     * @return string
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
        $features = $featureType->search(array_merge(array(
            'where'      => $this->buildCriteria($query->getConditions(), $featureType),
        ), $args));

        $geometryField = $featureType->getGeomField();

        $formatted = array();
        foreach ($features as $feature) {
            $properties = $feature->toArray();
            // remove (variable) geometry column from properties
            unset($properties[$geometryField]);
            $formatted[] = array(
                'geometry' => $feature->getGeom(),  // NOTE: WKT format
                'properties' => $properties,
            );
        }
        return array(
            'features' => $formatted,
        );
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