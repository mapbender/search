<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Entity\HKV;
use Mapbender\SearchBundle\Entity\Style;
use Mapbender\SearchBundle\Entity\StyleMap;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StyleMapManager
 *
 * @package Mapbender\SearchBundle\Component
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class StyleMapManager extends BaseManager
{
    const SERVICE_NAME = "mapbender.stylemap.manager";
    const TABLE_NAME   = "stylemaps";
    protected $styleManager;

    /**
     * StyleManager constructor.
     *
     * @param ContainerInterface|null $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        parent::__construct($container, self::TABLE_NAME);
        $this->styleManager = $this->container->get(StyleManager::SERVICE_NAME);
    }

    /**
     * @param array $args
     * @return StyleMap
     */
    public function createStyleMap($args)
    {
        $styleMap = new StyleMap($args);
        if (!isset($args["id"])) {
            $styleMap->setId($this->generateUUID());
        }
        $styleMap->setUserId($this->getUserId());
        return $styleMap;
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
        $styleMaps = $this->listStyleMaps(false);

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
            $this->createStyleMap($args),
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
     * @return StyleMap[]|null
     */
    public function listStyleMaps($fetchData)
    {
        $styleMaps = $this->db->getData($this->tableName, null, null, $this->getUserId());

        if ($fetchData) {
            $ids = array_keys($styleMaps);
            $this->styleManager->getByIds($styleMaps);
        }

        return $styleMaps;
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

        $style = $this->createStyleMap($args);
        return $style;
    }


    /**
     * @param string $id
     * @param string $scope
     * @param string $parentId
     * @return bool
     */
    public function remove($id, $scope = null, $parentId = null)
    {
        $list      = $this->listStyleMaps(false);
        $wasMissed = isset($list[ $id ]);
        unset($list[ $id ]);

        $this->db->saveData($this->tableName, $list, $scope, $parentId, $this->getUserId());

        return $wasMissed;

    }


    /**
     * @param string $styleMapId
     * @param string $styleId
     */
    public function addStyle($styleMapId, $styleId)
    {
        $styleMap = $this->getById($styleMapId);
        if ($styleMap != null) {
            $styleMap->addStyle($styleId);
            $style = $this->styleManager->getById($styleId);
            if($style==null) throw new Exception("Der Style kann nicht hinzugefügt werden. Er existiert nicht mehr.");
            $style->addStyleMap($styleMapId);
            $this->styleManager->save($style);
        }
        $this->save($styleMap);
    }

    /**
     * @param string $styleMapId
     * @param string $styleId
     * @return bool|StyleMap
     */
    public function removeStyle($styleMapId, $styleId)
    {
        $styleMap = $this->getById($styleMapId);
        if ($styleMap != null) {
            $style = $this->styleManager->getById($styleId);

            if (!$style) {
                throw new Exception("Der Style kann nicht gelöscht werden. Er gehört nicht zu der Stylemap.");
            }
            $style->removeStyleMapById($styleMapId);
            $styleMap->removeStyleById($styleId);

            $this->styleManager->save($style);
            $this->save($styleMap);
        }
        return false;
    }

}