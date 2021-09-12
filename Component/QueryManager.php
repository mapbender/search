<?php
namespace Mapbender\SearchBundle\Component;

use Mapbender\DataSourceBundle\Component\FeatureType;
use Mapbender\DataSourceBundle\Entity\Feature;
use Mapbender\SearchBundle\Entity\Query;
use Mapbender\SearchBundle\Entity\QueryCondition;

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
     * @param FeatureType $featureType
     * @param Query       $query
     * @param null|string $intersectGeometry
     * @param null        $srid
     * @return array
     */
    public function check(FeatureType $featureType, Query $query, $intersectGeometry = null, $srid = null)
    {
        $connection  = $featureType->getConnection();
        $driver      = $featureType->getDriver();
        $sql = $this->buildSql($featureType, $query, true);

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
     * @param FeatureType $featureType
     * @param Query  $query
     * @param bool   $count Count queries
     * @param null   $fieldNames
     * @param string $tableAliasName
     * @return string
     */
    public function buildSql(FeatureType $featureType, Query $query, $count = false, $fieldNames = null, $tableAliasName = 't')
    {
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
     * @param FeatureType $featureType
     * @param Query $query
     * @param array $args
     * @return array
     */
    public function fetchQuery(FeatureType $featureType, Query $query, array $args = array())
    {
        $features = $featureType->search(array_merge(array(
            'where'      => $this->buildCriteria($query->getConditions(), $featureType),
        ), $args));
        $results = array('features' => array());
        foreach ($features as $feature) {
            /** @var Feature $feature */

            $results['features'][] = array(
                'geometry' => $feature->getGeom(),
                'id' => $feature->getId(),
                'properties' => $feature->getAttributes(),
            );
        }
        return $results;
    }
}
