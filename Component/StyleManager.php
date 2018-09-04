<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Entity\UniqueBaseEntity;
use Mapbender\SearchBundle\Entity\Style;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StyleManager
 *
 * @package Mapbender\SearchBundle\Component
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class StyleManager extends BaseManager
{
    /**
     * StyleManager constructor.
     *
     * @param ContainerInterface|null $container
     * @param string $sqlitePath
     */
    public function __construct(ContainerInterface $container, $sqlitePath)
    {
        parent::__construct($container, $sqlitePath);
    }

    /**
     * Create style object
     *
     * @param $args
     * @return Style
     */
    public function create($args)
    {
        $style = new Style($args);
        if (!isset($args['id'])) {
            $style->setId($this->generateUUID());
        }
        return $style;
    }


    /**
     * Save style.
     *
     * @param      $style
     * @return UniqueBaseEntity|Style|null
     */
    public function save($style)
    {
        $styles        = $this->getAll();
        $id            = $style->getId();
        $styles[ $id ] = $style;
        $this->db->saveData($this->tableName, $styles, null, null, $this->getUserId());
        return $style;
    }

    /**
     * Get StyleMap by id
     *
     * @param string $id
     * @return Style|null
     */
    public function getById($id)
    {
        $styles = $this->getAll();

        return isset($styles[ $id ]) ? $styles[ $id ] : null;
    }

    /**
     * Get StyleMap by ids
     *
     * @param $ids
     * @return \Mapbender\SearchBundle\Entity\Style[]
     */
    public function getByIds($ids)
    {
        $styles    = array();
        $styleMaps = $this->getAll();

        foreach ($styleMaps as $key => $value) {
            if (in_array($key, $ids)) {
                $styles[ $key ] = $value;
            }
        }

        return $styles;
    }

    /**
     * List all StyleMaps
     *
     * @return Style[]
     */
    public function getAll()
    {
        $styles = $this->db->getData($this->tableName, null, null, $this->getUserId());
        return $styles ? $styles : array();
    }

    /**
     * @param string $id
     * @return bool
     */
    public function remove($id)
    {
        $list      = $this->getAll();
        $wasMissed = isset($list[ $id ]);
        unset($list[ $id ]);
        $this->db->saveData($this->tableName, $list, null, null, $this->getUserId());

        return $wasMissed;
    }
}