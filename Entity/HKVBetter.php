<?php
/**
 * Created by PhpStorm.
 * User: jheinrich
 * Date: 13.03.19
 * Time: 16:58
 */

namespace Mapbender\SearchBundle\Entity;

use Eslider\Entity\HKV;

class HKVBetter extends HKV
{
    /**
     * @return bool
     */
    public function hasChildren()
    {
        if(!$this->getChildren())
            return 0;
        return count($this->getChildren()) > 0;
    }
}