<?php
namespace Mapbender\SearchBundle\Entity;

use Eslider\Entity\BaseEntity;

/**
 * Class QuerySchema
 *
 * @package Mapbender\SearchBundle\Entity
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class QuerySchema extends BaseEntity
{
    /** @var string Feature type name or ID */
    protected $featureType;

    /** @var string Maximal results count */
    protected $maxResults = 1000;

    /** @var string Maximal results count */
    protected $maxExtend = 25000;

    /** @var array Fields */
    protected $fields = array();

    /** @var array TODO: Export configuration */
    protected $export = array();

    /** @var array TODO: Print configuration */
    protected $print = array();

    /** @var string TODO: Schema title */
    protected $title;

    /**
     * @return string Feature type name
     */
    public function getFeatureType()
    {
        return $this->featureType;
    }

    /**
     * @return string
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * @return string
     */
    public function getMaxExtend()
    {
        return $this->maxExtend;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }
}