<?php


namespace Mapbender\SearchBundle\Entity;

abstract class Base
{
    protected $values;
    /* @var string|null */
    protected $id;

    public function __construct($values)
    {
        $this->values = $this->getDefaults();
        if ($values) {
            $this->values = array_intersect_key($values, $this->values) + $this->values;
        }
    }

    public function toArray()
    {
        return $this->values;
    }

    protected function getDefaults()
    {
        return array(
            'id' => null,
            'userId' => null,
        );
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->values['id'];
    }

    /**
     * @param string|null $id
     */
    public function setId($id)
    {
        $this->values['id'] = $id;
    }

    /**
     * @return string|null
     */
    public function getUserId()
    {
        return $this->values['userId'];
    }

    /**
     * @param string|null $userId
     */
    public function setUserId($userId)
    {
        $this->values['userId'] = $userId;
    }
}
