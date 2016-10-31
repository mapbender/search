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
    /** @var string Name * */
    protected $name;

    /* @var Style[] Style list */
    protected $styles = array();

    /** @var string userId * */
    protected $userId;

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
     * @param string $id
     * @return string|boolean
     */
    public function removeStyleById($id)
    {
        $hasStyle = isset($this->styles[ $id ]);
        if ($hasStyle) {
            unset($this->styles[ $id ]);
        }
        return $hasStyle;
    }

    /**
     * @param $id
     * @return string
     */
    public function addStyle($id)
    {
        $this->styles[ $id ] = $id;
        return $this;
    }
}