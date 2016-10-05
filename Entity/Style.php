<?php

namespace Mapbender\SearchBundle\Entity;
use Eslider\Entity\UniqueBaseEntity;

/**
 * Class Style
 *
 * @package Mapbender\DataSourceBundle\Entity
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class Style extends UniqueBaseEntity
{

    /* @var int BorderSize */
    protected $borderSize;

    /* @var string BorderColor */
    protected $borderColor;

    /* @var int BorderAlpha */
    protected $borderAlpha;

    /* @var int BackgroundSize */
    protected $backgroundSize;

    /* @var int BackgroundAlpha */
    protected $backgroundAlpha;

    /**
     * @return int
     */
    public function getBorderSize()
    {
        return $this->borderSize;
    }

    /**
     * @param int $borderSize
     * @return Style
     */
    public function setBorderSize($borderSize)
    {
        $this->borderSize = $borderSize;
        return $this;
    }

    /**
     * @return string
     */
    public function getBorderColor()
    {
        return $this->borderColor;
    }

    /**
     * @param string $borderColor
     * @return Style
     */
    public function setBorderColor($borderColor)
    {
        $this->borderColor = $borderColor;
        return $this;
    }

    /**
     * @return int
     */
    public function getBorderAlpha()
    {
        return $this->borderAlpha;
    }

    /**
     * @param int $borderAlpha
     * @return Style
     */
    public function setBorderAlpha($borderAlpha)
    {
        $this->borderAlpha = $borderAlpha;
        return $this;
    }

    /**
     * @return int
     */
    public function getBackgroundSize()
    {
        return $this->backgroundSize;
    }

    /**
     * @param int $backgroundSize
     * @return Style
     */
    public function setBackgroundSize($backgroundSize)
    {
        $this->backgroundSize = $backgroundSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getBackgroundAlpha()
    {
        return $this->backgroundAlpha;
    }

    /**
     * @param int $backgroundAlpha
     * @return Style
     */
    public function setBackgroundAlpha($backgroundAlpha)
    {
        $this->backgroundAlpha = $backgroundAlpha;
        return $this;
    }

    /**
     * @return string
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * @param string $backgroundColor
     * @return Style
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    /* @var string BackgroundColor */
    protected $backgroundColor;


}