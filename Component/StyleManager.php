<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Entity\UniqueBaseEntity;
use Mapbender\SearchBundle\Entity\Style;
use Mapbender\SearchBundle\Entity\StyleMap;
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
     */
    public function __construct(ContainerInterface $container = null)
    {
        parent::__construct($container, 'styles');
    }

    /**
     * Create style object
     *
     * @param $args
     * @return Style
     */
    public function createStyle($args)
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
     * @param int  $scope
     * @param int  $parentId
     * @return UniqueBaseEntity|Style|null
     */
    public function save($style, $scope = null, $parentId = null)
    {
        $styles        = $this->listStyles();
        $id            = $style->getId();
        $styles[ $id ] = $style;
        $this->db->saveData($this->tableName, $styles, $scope, $parentId, $this->getUserId());
        return $style;
    }

    /**
     * Save query
     *
     * @param array $args
     * @param null  $scope
     * @param null  $parentId
     * @return Style
     */
    public function saveArray($args, $scope = null, $parentId = null)
    {
        return $this->save(
            $this->createStyle($args),
            $scope,
            $parentId
        );
    }

    /**
     * Get StyleMap by id
     *
     * @param string $id
     * @return Style|null
     */
    public function getById($id)
    {
        $styles = $this->listStyles();

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
        $styleMaps = $this->listStyles();

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
    public function listStyles()
    {
        $styles = $this->db->getData($this->tableName, null, null, $this->getUserId());
        return $styles ? $styles : array();
    }

    /**
     * @param $args
     * @return StyleMap
     */
    public function create($args)
    {
        if ($args == null) {
            return null;
        }

       return $this->createStyle($args);
    }


    /**
     * @param string  $id
     * @param string  $scope
     * @param string  $parentId
     * @return bool
     */
    public function remove($id, $scope = null, $parentId = null)
    {
        $list      = $this->listStyles();
        $wasMissed = isset($list[ $id ]);
        unset($list[ $id ]);
        $this->db->saveData($this->tableName, $list, $scope, $parentId, $this->getUserId());

        return $wasMissed;
    }

    /**
     * Get schema styles
     *
     * @param $scheme
     * @return \Mapbender\SearchBundle\Entity\Style[]
     */
    public function getSchemaStyles($scheme)
    {
        $styles      = array();
        $getAnyStyle = isset($scheme['group']) && $scheme['group'] == 'all';

        foreach ($this->listStyles() as $style) {
            if (!$getAnyStyle && $style->schemaName != $scheme['featureTypeName']) {
                continue;
            }
            $styleData       = $style->toArray();
            $styleData['id'] = $style->getId();

            unset($styleData['userId']);
            unset($styleData['name']);
            unset($styleData['styleMaps']);
            unset($styleData['title']);

            $styles[ $style->featureId ] = $styleData;
        }
        return $styles;
    }
}