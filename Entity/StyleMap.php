<?php

namespace Mapbender\SearchBundle\Entity;
use Eslider\Entity\UniqueBaseEntity;

/**
 * Class StyleMap
 *
 * @package Mapbender\DataSourceBundle\Entity
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class StyleMap extends UniqueBaseEntity
{
    /* @var int Test */
    protected $test;

    /* @var Style SelectStyle */
    protected $selectStyle;

    /* @var Style DefaultStyle */
    protected $defaultStyle;

    /**
     * @return int
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @param int $test
     * @return StyleMap
     */
    public function setTest($test)
    {
        $this->test = $test;
        return $this;
    }

    /**
     * @return Style
     */
    public function getSelectStyle()
    {
        return $this->selectStyle;
    }

    /**
     * @param Style $selectStyle
     * @return StyleMap
     */
    public function setSelectStyle($selectStyle)
    {
        $this->selectStyle = $selectStyle;
        return $this;
    }

    /**
     * @return Style
     */
    public function getDefaultStyle()
    {
        return $this->defaultStyle;
    }

    /**
     * @param Style $defaultStyle
     * @return StyleMap
     */
    public function setDefaultStyle($defaultStyle)
    {
        $this->defaultStyle = $defaultStyle;
        return $this;
    }


}