<?php

namespace Mapbender\SearchBundle\Entity;

/**
 * Class SearchQuery
 *
 * @package Mapbender\SearchBundle\Entity
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class Query extends UniqueBase
{
    /** @var string Name */
    public $name;

    /** @var QueryCondition[] Conditions */
    public $conditions;

    /** @var array */
    public $fields;

    /** @var int $userId */
    public $userId;

    /** @var StyleMap StyleMap */
    public $styleMap;

    /** @var string Schema ID or name */
    public $schemaId;

    /** @var string SQL */
    public $sql;

    /** @var string Where */
    public $where;

    /** @var bool Look for extend only? */
    public $extendOnly = true;

    /** @var bool Only for Export? */
    public $exportOnly = false;

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
    public function getSchemaId()
    {
        return $this->schemaId;
    }

    /**
     * @param string $schemaId
     * @return Query
     */
    public function setSchemaId($schemaId)
    {
        $this->schemaId = $schemaId;
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
        $queryConditions = $this->conditions;
        return $queryConditions;
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
     * @param array $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array
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

    /**
     * @return bool
     */
    public function hasConditions()
    {
        return count($this->conditions) > 0;
    }
}
