<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Entity\HKV;
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
    const SERVICE_NAME = "mapbender.style.manager";
    const TABLE_NAME   = "styles";

    /**
     * StyleManager constructor.
     *
     * @param ContainerInterface|null $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        parent::__construct($container, self::TABLE_NAME);
    }

    /**
     * @param array $args
     * @return StyleMap
     */
    public function createStyleMap($args)
    {
        $styleMap = new StyleMap($args);
        $styleMap->setId($this->generateUUID());
        $styleMap->setUserId($this->getUserId());
        return $styleMap;
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
        $style->setId($this->generateUUID());
        $style->setUserId($this->getUserId());
        return $style;
    }


    /**
     * Save style.
     *
     * @param      $styleMap
     * @param int  $scope
     * @param int  $parentId
     * @return StyleMap
     */
    public function save($styleMap, $scope = null, $parentId = null)
    {
        $styleMaps = $this->listStyleMaps();

        if ($styleMaps == null) {
            $styleMaps = array();
        }

        $styleMaps[ $styleMap->getId() ] = $styleMap;
        $result                          = $this->db->saveData($this->tableName, $styleMaps, $scope, $parentId, $this->getUserId());

        $children = $result->getChildren();

        foreach ($children as $key => $child) {
            if ($child->getKey() == $styleMap->getId()) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Save query
     *
     * @param array $args
     * @param null  $scope
     * @param null  $parentId
     * @return StyleMap
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
     * @param int $id
     * @return StyleMap|null
     */
    public function getById($id)
    {
        $styleMaps = $this->listStyleMaps();
        return isset($styleMaps[ $id ]) ? $styleMaps[ $id ] : null;

    }

    /**
     * List all StyleMaps
     *
     * @return HKV|null
     */
    public function listStyleMaps()
    {
        return $this->db->getData($this->tableName, null, null, $this->getUserId());
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

        $style = $this->createStyle($args);

        return $this->createStyleMap(
            array(
                "styles" => array($style->getName() => $style)
            )
        );
    }
}