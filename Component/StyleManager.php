<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Entity\HKV;
use Eslider\Entity\HKVSearchFilter;
use Mapbender\CoreBundle\Component\SecurityContext;
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
     * @param $args
     * @return Style
     */
    public function createStyle($args)
    {
        $styleMap = new Style($args);
        $styleMap->setId($this->generateUUID());
        return $styleMap;
    }


    /**
     * Save style.
     *
     * @param      $styleMap
     * @param int  $scope
     * @param int  $parentId
     * @return HKV
     */
    public function save($styleMap, $scope = null, $parentId = null)
    {
        $result = $this->db->saveData($this->tableName, $styleMap, $scope, $parentId, $this->getUserId());
        return $result;
    }

    /**
     * save query
     *
     * @param array $array
     * @return HKV
     */
    public function saveArray($array, $scope = null, $parentId = null)
    {
        $styleMap = $this->createStyleMap($array);
        $HKV      = $this->save($styleMap, $scope, $parentId);
        return $HKV;
    }


    /**
     * Get StyleMap by id
     *
     * @param int $id
     * @return StyleMap|null
     */
    public function getById($id)
    {
        $filter = new HKVSearchFilter();
        $filter->setKey($this->tableName);
        $filter->setUserId($this->getUserId());
        $hkv = $this->db->get($filter);

        $styleMap      = $hkv->getValue();
        $styleMapFound = $hkv != null &&
            $styleMap != null &&
            $styleMap->getId() == $id;

        return $styleMapFound ? $styleMap : null;

    }


    /**
     * List all StyleMaps
     *
     * @param int $id
     * @return HKV[]|null
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

        return $this->createStyleMap(array("styles" => array($style->getName() => $style)));
    }


}