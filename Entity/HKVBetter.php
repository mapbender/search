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
    public function __construct(array $data = null)
    {
        parent::__construct($data);

        $this->children = array();
    }

    /**
     * Set children
     *
     * @param HKV[] $children
     * @return $this
     */
    public function setChildren(array $children)
    {
        $_children = array();
        foreach ($children as $child) {
            if (is_array($child)) {
                $_children[] = new HKVBetter($child);
            } elseif (is_object($child) && $child instanceof HKV) {
                $_children[] = $child;
            }
        }
        $this->children = $_children;
        return $this;
    }

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
