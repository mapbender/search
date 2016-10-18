<?php
namespace Mapbender\SearchBundle\Entity;

use Eslider\Entity\UniqueBaseEntity;

/**
 * Class SearchQuery
 *
 * @package Mapbender\SearchBundle\Entity
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class QueryCondition extends UniqueBaseEntity
{
    /** @var string Name */
    protected $name;

    /** @var string $fieldName */
    protected $fieldName;

    /** @var string Operator */
    protected $operator;

    /** @var string Value */
    protected $value;

    /** @var string SQL */
    protected $sql;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return QueryCondition
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     * @return QueryCondition
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     * @return QueryCondition
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return QueryCondition
     */
    public function setValue($value)
    {
        $this->value = $value;
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
     * @return QueryCondition
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
        return $this;
    }

}