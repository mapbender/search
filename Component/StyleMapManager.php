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
     * @param      $style
     * @param int  $scope
     * @param int  $parentId
     * @return StyleMap
     */
    public function save($style, $scope = null, $parentId = null)
    {
        $styleMaps = $this->listStyleMaps(false);

        if ($styleMaps == null) {
            $styleMaps = array();
        }

        $styleMaps[ $style->getId() ] = $style;
        $result                       = $this->db->saveData($this->tableName, $styleMaps, $scope, $parentId, $this->getUserId());

        $children = $result->getChildren();

        foreach ($children as $key => $child) {
            if ($child->getKey() == $style->getId()) {
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
    public function getById($id, $fetchData = false)
    {
        $styleMaps = $this->listStyleMaps($fetchData);
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
     * @param StyleManager $styleManager
     * @param string       $styleMapId
     * @param string       $styleId
     * @return bool
     */
    public function addStyle($styleMapId, $styleId)
    {
        $styleMap = $this->getById($styleMapId);
        if ($styleMap) {

            $style = $this->styleManager->getById($styleId);

            if (!$style) {
                throw new Exception("Der Style kann nicht hinzugefügt werden. Er existiert nicht mehr.");
            }

            $styleMap->addStyle($styleId);
            $style->addStyleMap($styleMapId);

            $this->styleManager->save($style);
            return $this->save($styleMap) ? true : false;
        }

        return false;
    }


    /**
     * @param StyleManager $styleManager
     * @param string       $styleMapId
     * @param string       $styleId
     * @return bool
     */
    public function removeStyle($styleMapId, $styleId)
    {
        $styleMap = $this->getById($styleMapId);
        if ($styleMap) {

            $style = $this->styleManager->getById($styleId);

            if (!$style) {
                throw new Exception("Der Style kann nicht gelöscht werden. Er gehört nicht zu der Stylemap.");
            }

            $style->removeStyleMapById($styleMapId);
            $styleMap->removeStyleById($styleId);

            $this->styleManager->save($style);
            return $this->save($styleMap) ? true : false;
        }

        return false;
    }

}