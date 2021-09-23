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

    /** @var array[]|null */
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
     * @return string
     */
    public function getSchemaId()
    {
        return $this->schemaId;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @return string
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * @return array[]|null
     */
    public function getConditions()
    {
        $queryConditions = $this->conditions;
        return $queryConditions;
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
}
