<?php
namespace Mapbender\SearchBundle\Entity;

use Eslider\Entity\UniqueBaseEntity;

/**
 * Class Field
 *
 * @package Mapbender\SearchBundle\Entity
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class Field extends UniqueBaseEntity
{
    /* @var string $name */
    protected $name;

    /* @var string $description */
    protected $description;

    /**
     * @param string $description
     * @return Field
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $name
     * @return Field
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


}