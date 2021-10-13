<?php

namespace Mapbender\SearchBundle\Entity;

/**
 * Class Style
 *
 * @package Mapbender\DataSourceBundle\Entity
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class Style extends UniqueBase
{
    /** @var string Name */
    public $name;
    /** @var String  Hex fill color.  Default is “#ee9900”. */
    public $fillColor;

    /** @var Number  Fill opacity (0-1).  Default is 0.4 */
    public $fillOpacity;

    /** @var Boolean  Set to false if no stroke is desired. */
    public $stroke;

    /** @var String  Hex stroke color.  Default is “#ee9900”. */
    public $strokeColor;

    /** @var Number  Stroke opacity (0-1).  Default is 1. */
    public $strokeOpacity;

    /** @var Number  Pixel stroke width.  Default is 1. */
    public $strokeWidth;

    /** @var String  Stroke cap type.  Default is “round”.  [butt | round | square] */
    public $strokeLinecap;

    /** @var String  Stroke dash style.  Default is “solid”.  [dot | dash | dashdot | longdash | longdashdot | solid] */
    public $strokeDashstyle;

    /** @var Boolean  Set to false if no graphic is desired. */
    public $graphic;

    /** @var Number  Pixel point radius.  Default is 6. */
    public $pointRadius;

    /** @var String  The text for an optional label.  For browsers that use the canvas renderer, this requires either fillText or mozDrawText to be available. */
    public $label;

    /** @var String  The font color for the label, to be provided like CSS. */
    public $fontColor;

    /** @var Number  Opacity (0-1) for the label */
    public $fontOpacity;

    /** @var String  The font family for the label, to be provided like in CSS. */
    public $fontFamily;

    /** @var String  The font size for the label, to be provided like in CSS. */
    public $fontSize;

    /** @var String  The font weight for the label, to be provided like in CSS. */
    public $fontWeight;

    /** @var int User ID */
    public $userId;

    /** @var String[] Style Maps which contain this style */
    public $styleMaps;

    /** @var */
    public $featureId;

    /** @var */
    public $schemaName;

    /**
     * Set user ID
     *
     * @param $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param \String[] $styleMaps
     * @return Style
     */
    public function setStyleMaps($styleMaps)
    {
        $this->styleMaps = $styleMaps;
        return $this;
    }

    /**
     * @return \String[]
     */
    public function getStyleMaps()
    {
        return $this->styleMaps;
    }

    /**
     * @return bool
     */
    public function canBeDeleted()
    {
        return empty($this->styleMaps);
    }
}
