<?php

namespace Mapbender\SearchBundle\Entity;

use Eslider\Entity\UniqueBaseEntity;
use Mapbender\CoreBundle\Component\SecurityContext;

/**
 * Class SearchQuery
 *
 * @package Mapbender\SearchBundle\Entity
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class Query extends UniqueBaseEntity
{
    /** @var string Name */
    protected $name;

    /** @var QueryCondition[] Conditions */
    protected $conditions;

    /** @var Field[] Fields */
    protected $fields;

    /** @var int $userId */
    protected $userId;

    /** @var StyleMap StyleMap */
    protected $styleMap;

    /** @var string SchemaName */
    protected $featureType;

    /** @var string SQL */
    protected $sql;

    /** @var string Where */
    protected $where;

    /** @var bool Look for extend only? */
    protected $extendOnly = true;


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Query
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @param int $userId
     * @return Query
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return StyleMap
     */
    public function getStyleMap()
    {
        return $this->styleMap;
    }

    /**
     * @param StyleMap $styleMap
     * @return Query
     */
    public function setStyleMap($styleMap)
    {
        $this->styleMap = $styleMap;
        return $this;
    }

    /**
     * @return string
     */
    public function getFeatureType()
    {
        return $this->featureType;
    }

    /**
     * @param string $featureType
     * @return Query
     */
    public function setFeatureType($featureType)
    {
        $this->featureType = $featureType;
        return $this;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @param string $sql
     * @return Query
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * @return string
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * @param string $where
     * @return Query
     */
    public function setWhere($where)
    {
        $this->where = $where;
        return $this;
    }


    /**
     * @return QueryCondition[]
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param QueryCondition $condition
     */
    public function addCondition(QueryCondition $condition)
    {
        $this->conditions[ $condition->getId() ] = $condition;
    }


    /**
     * @param $id
     * @return QueryCondition|boolean
     */
    public function removeCondition($id)
    {
        if (isset($this->conditions[ $id ])) {
            $queryCondition = $this->conditions[ $id ];
            unset($this->conditions[ $id ]);
            return $queryCondition;
        }
        return false;
    }

    /**
     * @param Field[] $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return boolean
     */
    public function isExtendOnly()
    {
        return $this->extendOnly;
    }

    /**
     * @param boolean $extendOnly
     */
    public function setExtendOnly($extendOnly)
    {
        $this->extendOnly = $extendOnly;
    }


}