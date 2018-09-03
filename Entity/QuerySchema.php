<?php
namespace Mapbender\SearchBundle\Entity;

/**
 * Class QuerySchema
 *
 * @package Mapbender\SearchBundle\Entity
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class QuerySchema extends Base
{
    /** @var string Feature type name or ID */
    public $featureType;

    /** @var string Maximal results count */
    public $maxResults = 500;

    /** @var string Maximal results count */
    public $maxExtend = 25000;

    /** @var array Fields */
    public $fields = array();

    /** @var array TODO: Export configuration */
    public $export = array();

    /** @var array TODO: Print configuration */
    public $print = array();

    /** @var string TODO: Schema title */
    public $title;

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