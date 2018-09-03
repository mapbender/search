<?php

namespace Mapbender\SearchBundle\Entity;

use FOM\CoreBundle\Component\ExportResponse;

/**
 * Class ExportRequest
 *
 * @package Mapbender\SearchBundle\Entity
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class ExportRequest extends UniqueBase
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
}