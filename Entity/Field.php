<?php
namespace Mapbender\SearchBundle\Entity;

/**
 * Class Field
 *
 * @package Mapbender\SearchBundle\Entity
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class Field extends UniqueBase
{
    /* @var string $name */
    public $name;

    /* @var string $description */
    public $description;

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