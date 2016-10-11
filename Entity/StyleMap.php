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

    /* @var Style[] Styles */
    protected $styles;

    /** @var string userId * */
    protected $userId;

    public function __construct(array &$data = null)
    {
        $this->styles = array();
        parent::__construct($data);
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return Style[]
     */
    public function getStyles()
    {
        return $this->styles;
    }

    /**
     * @return Style
     */
    public function getStyle($name = 'default')
    {
        return $this->styles[ $name ];
    }


    /**
     * @param Style  $style
     * @param string $name
     * @return Style
     */
    public function setStyle(Style $style, $name = 'default')
    {
        $this->styles[ $name ] = $style;
        return $style;
    }


    /**
     * @param Style $style
     * @return Style|boolean
     */
    public function removeStyle(Style $style)
    {
        $result = false;
        foreach ($this->styles as $key => $value) {
            if ($value->getId() == $style->getId()) {
                $result = $this->styles[ $key ];
                unset($this->styles[ $key ]);
                return $result;
            }
        }
        return $result;
    }

    /**
     * @param string $name
     * @return Style|boolean
     */
    public function removeStyleByName($name)
    {
        $result = false;
        foreach ($this->styles as $key => $value) {
            if ($value->getName() == $name) {
                $result = $this->styles[ $key ];
                unset($this->styles[ $key ]);
                return $result;
            }
        }
        return $result;
    }

    /**
     * @param string $name
     * @param Style  $map
     * @return StyleMap
     */
    public function addStyle($name, $map)
    {
        $this->styles[ $name ] = $map;
        return $this;
    }


    public function fill(array &$data)
    {
        parent::fill($data);
    }


}