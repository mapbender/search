<?php


namespace Mapbender\SearchBundle\Entity;

/**
 * Drop-in replacement for both Eslider\Entity\BaseEntity and Mapbender\DataSourceBundle\Entity\BaseConfiguration
 * * satisfies construct-with-array behavior
 * * offers compatible 'fill' and 'toArray' methods
 */
class Base
{
    public function __construct($values)
    {
        if ($values) {
            $this->fill($values);
        }
    }

    public function fill($values)
    {
        $commonKeys = array_keys(array_intersect_key($this->toArray(), $values));
        foreach ($commonKeys as $k) {
            $this->{$k} = $values[$k];
        }
    }

    /**
     * Export data
     *
     * @return mixed
     */
    public function toArray()
    {
        return get_object_vars($this);
    }
}
