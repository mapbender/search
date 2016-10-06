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


    /** @var string Name **/
    protected $name;

    /**
     * Border
     **/

    /** @var int BorderSize **/
    protected $borderSize;

    /** @var string BorderColor **/
    protected $borderColor;

    /** @var int BorderAlpha **/
    protected $borderAlpha;


    /**
     * Background
     **/

    /** @var int BackgroundSize **/
    protected $backgroundSize;

    /** @var int BackgroundAlpha **/
    protected $backgroundAlpha;

    /** @var string BackgroundColor **/
    protected $backgroundColor;


    /**
     * Graphic
     **/

    /** @var string $externalGraphic **/
    protected $externalGraphic = '$(thumbnail)';

    /** @var string $graphicWidth **/
    protected $graphicWidth;

    /** @var string $graphicHeight **/
    protected $graphicHeight;

    /** @var string $graphicOpacity **/
    protected $graphicOpacity;

    /** @var string $graphicXOffset **/
    protected $graphicXOffset;

    /** @var string $graphicYOffset **/
    protected $graphicYOffset;

    /** @var string $graphicName **/
    protected $graphicName;

    /**
     * Vector
     **/

    /** @var string fillOpacity **/
    protected $fillOpacity;

    /** @var string fillColor **/
    protected $fillColor;

    /** @var string strokeColor **/
    protected $strokeColor;

    /** @var string StrokeOpacity **/
    protected $strokeOpacity;

    /** @var string StrokeWidth **/
    protected $strokeWidth;

    /** @var string StrokeLinecap **/
    protected $strokeLinecap;

    /** @var string StrokeDashstyle **/
    protected $strokeDashstyle;

    /** @var int pointRadius **/
    protected $pointRadius;

    /**
     * Misc
     **/

    /** @var string[] PointerEvents **/
    protected $pointerEvents;

    /** @var string Cursor **/
    protected $cursor;

    /** @var string Rotation **/
    protected $rotation;

    /** @var string Display **/
    protected $display;


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getExternalGraphic()
    {
        return $this->externalGraphic;
    }

    /**
     * @param string $externalGraphic
     * @return Style
     */
    public function setExternalGraphic($externalGraphic)
    {
        $this->externalGraphic = $externalGraphic;
        return $this;
    }

    /**
     * @return string
     */
    public function getGraphicWidth()
    {
        return $this->graphicWidth;
    }

    /**
     * @param string $graphicWidth
     * @return Style
     */
    public function setGraphicWidth($graphicWidth)
    {
        $this->graphicWidth = $graphicWidth;
        return $this;
    }

    /**
     * @return string
     */
    public function getGraphicHeight()
    {
        return $this->graphicHeight;
    }

    /**
     * @param string $graphicHeight
     * @return Style
     */
    public function setGraphicHeight($graphicHeight)
    {
        $this->graphicHeight = $graphicHeight;
        return $this;
    }

    /**
     * @return string
     */
    public function getGraphicOpacity()
    {
        return $this->graphicOpacity;
    }

    /**
     * @param string $graphicOpacity
     * @return Style
     */
    public function setGraphicOpacity($graphicOpacity)
    {
        $this->graphicOpacity = $graphicOpacity;
        return $this;
    }

    /**
     * @return string
     */
    public function getGraphicXOffset()
    {
        return $this->graphicXOffset;
    }

    /**
     * @param string $graphicXOffset
     * @return Style
     */
    public function setGraphicXOffset($graphicXOffset)
    {
        $this->graphicXOffset = $graphicXOffset;
        return $this;
    }

    /**
     * @return string
     */
    public function getGraphicYOffset()
    {
        return $this->graphicYOffset;
    }

    /**
     * @param string $graphicYOffset
     * @return Style
     */
    public function setGraphicYOffset($graphicYOffset)
    {
        $this->graphicYOffset = $graphicYOffset;
        return $this;
    }

    /**
     * @return string
     */
    public function getGraphicName()
    {
        return $this->graphicName;
    }

    /**
     * @param string $graphicName
     * @return Style
     */
    public function setGraphicName($graphicName)
    {
        $this->graphicName = $graphicName;
        return $this;
    }

    /**
     * @return string
     */
    public function getFillOpacity()
    {
        return $this->fillOpacity;
    }

    /**
     * @param string $fillOpacity
     * @return Style
     */
    public function setFillOpacity($fillOpacity)
    {
        $this->fillOpacity = $fillOpacity;
        return $this;
    }

    /**
     * @return string
     */
    public function getFillColor()
    {
        return $this->fillColor;
    }

    /**
     * @param string $fillColor
     * @return Style
     */
    public function setFillColor($fillColor)
    {
        $this->fillColor = $fillColor;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrokeColor()
    {
        return $this->strokeColor;
    }

    /**
     * @param string $strokeColor
     * @return Style
     */
    public function setStrokeColor($strokeColor)
    {
        $this->strokeColor = $strokeColor;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrokeOpacity()
    {
        return $this->strokeOpacity;
    }

    /**
     * @param string $strokeOpacity
     * @return Style
     */
    public function setStrokeOpacity($strokeOpacity)
    {
        $this->strokeOpacity = $strokeOpacity;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrokeWidth()
    {
        return $this->strokeWidth;
    }

    /**
     * @param string $strokeWidth
     * @return Style
     */
    public function setStrokeWidth($strokeWidth)
    {
        $this->strokeWidth = $strokeWidth;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrokeLinecap()
    {
        return $this->strokeLinecap;
    }

    /**
     * @param string $strokeLinecap
     * @return Style
     */
    public function setStrokeLinecap($strokeLinecap)
    {
        $this->strokeLinecap = $strokeLinecap;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrokeDashstyle()
    {
        return $this->strokeDashstyle;
    }

    /**
     * @param string $strokeDashstyle
     * @return Style
     */
    public function setStrokeDashstyle($strokeDashstyle)
    {
        $this->strokeDashstyle = $strokeDashstyle;
        return $this;
    }

    /**
     * @return int
     */
    public function getPointRadius()
    {
        return $this->pointRadius;
    }

    /**
     * @param int $pointRadius
     * @return Style
     */
    public function setPointRadius($pointRadius)
    {
        $this->pointRadius = $pointRadius;
        return $this;
    }

    /**
     * @return \string[]
     */
    public function getPointerEvents()
    {
        return $this->pointerEvents;
    }

    /**
     * @param \string[] $pointerEvents
     * @return Style
     */
    public function setPointerEvents($pointerEvents)
    {
        $this->pointerEvents = $pointerEvents;
        return $this;
    }

    /**
     * @return string
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * @param string $cursor
     * @return Style
     */
    public function setCursor($cursor)
    {
        $this->cursor = $cursor;
        return $this;
    }

    /**
     * @return string
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * @param string $rotation
     * @return Style
     */
    public function setRotation($rotation)
    {
        $this->rotation = $rotation;
        return $this;
    }

    /**
     * @return string
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @param string $display
     * @return Style
     */
    public function setDisplay($display)
    {
        $this->display = $display;
        return $this;
    }


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


}