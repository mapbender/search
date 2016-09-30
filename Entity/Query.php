<?php
namespace Mapbender\SearchBundle\Entity;

/**
 * Class SearchQuery
 *
 * @package Mapbender\SearchBundle\Entity
 */
class Query
{
    /** @var string Name */
    protected $name;

    /** @var QueryCondition[] Queries */
    protected $conditions;

    /**
     * Query constructor.
     */
    public function __construct()
    {

    }

    /**
     * @return QueryCondition[]
     */
    public function getConditions()
    {
        return $this->conditions;
    }
}