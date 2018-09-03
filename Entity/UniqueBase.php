<?php


namespace Mapbender\SearchBundle\Entity;


/**
 * Drop-in replacement for Eslider\Entity\UniqueBaseEntity
 */
class UniqueBase extends Base
{
    /* @var string */
    public $id;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
}
