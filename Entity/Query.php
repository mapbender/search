<?php

namespace Mapbender\SearchBundle\Entity;

/**
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class Query extends Base
{
    protected function getDefaults()
    {
        return parent::getDefaults() + array(
            'name' => null,
            'schemaId' => null,
            'conditions' => array(),
            'fields' => array(),
            'styleMap' => null,
            'extendOnly' => true,
            'exportOnly' => false,
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->values['name'];
    }

    /**
     * @return string
     */
    public function getSchemaId()
    {
        return $this->values['schemaId'];
    }

    /**
     * @return array[]|null
     */
    public function getConditions()
    {
        return $this->values['conditions'];
    }

    /**
     * @return boolean
     */
    public function isExtendOnly()
    {
        return $this->values['extendOnly'];
    }
}
