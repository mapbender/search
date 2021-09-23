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
            $whereConditions[] = $connection->quoteIdentifier($condition['fieldName'])
                . ' ' . $condition['operator']
                . ' ' . $connection->quote($condition['value']);
        }
        return implode(' AND ', $whereConditions);
    }

    /**
     * @param FeatureType $featureType
     * @param Query       $query
     * @param mixed[] $params
     * @return array
     */
    public function check(FeatureType $featureType, Query $query, $params)
    {
        if (!$query->isExtendOnly()) {
            unset($params['intersect']);
        }
        $t0 = (microtime(true) * 1000);
        $count = $featureType->count(array_merge(array(
            'where' => $this->buildCriteria($query->getConditions(), $featureType),
        ), $params));

        $runningTime = (microtime(true) * 1000) - $t0;


        $result = array(
            'count'         => $count,
            'executionTime' => round($runningTime) . 'ms',
        );

        return $result;
    }

    /**
     * @param FeatureType $featureType
     * @param Query  $query
     * @param null   $fieldNames
     * @param string $tableAliasName
     * @return string
     */
    public function buildSql(FeatureType $featureType, Query $query, $fieldNames = null, $tableAliasName = 't')
    {
        $connection  = $featureType->getConnection();
        $fields      = array();

        $fieldNames = $fieldNames ? $fieldNames : $featureType->getFields();
        foreach ($fieldNames as $fieldName) {
            $fields[] = $connection->quoteIdentifier($fieldName);
        }

        $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $connection->quoteIdentifier($featureType->getTableName()) . ' ' . $tableAliasName . ' WHERE 1=1 ';

        if ($conditions = $query->getConditions()) {
            $sql .= ' AND ' . $this->buildCriteria($conditions, $featureType);
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
