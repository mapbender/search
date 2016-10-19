<?php

namespace Mapbender\SearchBundle\Entity;

use Eslider\Entity\UniqueBaseEntity;
use FOM\CoreBundle\Component\ExportResponse;

/**
 * Class ExportRequest
 *
 * @package Mapbender\SearchBundle\Entity
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class ExportRequest extends UniqueBaseEntity
{
    /** @var string */
    protected $type;

    /**@var string */
    protected $filename;

    /**@var string */
    protected $encodingFrom;

    /** @var string */
    protected $enclosure;

    /** @var string */
    protected $delimiter;

    /** @var boolean */
    protected $enableDownload;


    /**
     * ExportRequest constructor.
     *
     * @param array     $request
     * @param \string[] $ids
     * @param string    $type
     * @param string    $filename
     * @param string    $encodedFrom
     * @param string    $enclosure
     * @param string    $delimiter
     * @param bool      $enableDownload
     */

    public function __construct($request, $type = ExportResponse::TYPE_CSV, $filename = 'export',
        $encodedFrom = "UTF-8", $enclosure = '"', $delimiter = ',', $enableDownload = true)
    {

        $this->type           = $type;
        $this->filename       = $filename;
        $this->encodingFrom   = $encodedFrom;
        $this->enclosure      = $enclosure;
        $this->delimiter      = $delimiter;
        $this->enableDownload = $enableDownload;

        if (is_array($request)) {
            $this->fill($request);
        }
    }

    /**
     * @return \string[]
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @param \string[] $ids
     * @return ExportRequest
     */
    public function setIds($ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ExportRequest
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return ExportRequest
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncodingFrom()
    {
        return $this->encodingFrom;
    }

    /**
     * @param string $encodingFrom
     * @return ExportRequest
     */
    public function setEncodingFrom($encodingFrom)
    {
        $this->encodingFrom = $encodingFrom;
        return $this;
    }

    /**
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * @param string $enclosure
     * @return ExportRequest
     */
    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;
        return $this;
    }

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     * @return ExportRequest
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnableDownload()
    {
        return $this->enableDownload;
    }

    /**
     * @param boolean $enableDownload
     * @return ExportRequest
     */
    public function setEnableDownload($enableDownload)
    {
        $this->enableDownload = $enableDownload;
        return $this;
    }


}