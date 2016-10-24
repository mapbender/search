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
    /** @var string[] */
    public $ids = array();

    /** @var string */
    public $type = ExportResponse::TYPE_CSV;

    /** @var string */
    public $filename = 'export';

    /** @var string */
    public $encodingFrom = "UTF-8";

    /** @var string */
    public $enclosure = '"';

    /** @var string */
    public $delimiter = ',';

    /** @var boolean */
    public $enableDownload = true;

    /**
     * Get id list
     *
     * @return \string[]
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @param string $ids
     * @return ExportRequest
     */
    public function setIds($ids)
    {
        $this->ids = is_string($ids) ? explode(",", $ids) : $ids;
        return $this;
    }
}