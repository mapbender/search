<?php
namespace Mapbender\SearchBundle\Component;

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
    /** @var StyleManager */
    protected $styleManager;

    /**
     * StyleManager constructor.
     *
     * @param ContainerInterface|null $container
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function __construct(ContainerInterface $container = null)
    {
        parent::__construct($container, 'stylemaps');
        $this->styleManager = $this->container->get('mapbender.style.manager');
    }

    /**
     * @param array $args
     * @return StyleMap
     */
    public function create($args)
    {
        $styleMap = new StyleMap($args);
        if (!isset($args['id'])) {
            $styleMap->setId($this->generateUUID());
        }
        $styleMap->setUserId($this->getUserId());
        return $styleMap;
    }


    /**
     * Save style.
     *
     * @param StyleMap $styleMap
     * @param int      $scope
     * @param int      $parentId
     * @return StyleMap
     */
    public function save($styleMap, $scope = null, $parentId = null)
    {
        $styleMaps                       = $this->listStyleMaps(false);
        $styleMaps[ $styleMap->getId() ] = $styleMap;
        $this->db->saveData($this->tableName, $styleMaps, $scope, $parentId, $this->getUserId());
        return $styleMap;
    }

    /**
     * Save query
     *
     * @param array $args
     * @return StyleMap
     */
    public function saveArray($args)
    {
        return $this->save($this->create($args));
    }

    /**
     * Get StyleMap by id
     *
     * @param int  $id
     * @param bool $fetchData
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
     * @param bool $fetchData Default = false
     * @return \Mapbender\SearchBundle\Entity\StyleMap[]|null
     */
    public function listStyleMaps($fetchData = false)
    {
        $styleMaps = $this->db->getData($this->tableName, null, null, $this->getUserId());

        if ($fetchData) {
            $ids = array_keys($styleMaps);
            $this->styleManager->getByIds($styleMaps);
        }

        return $styleMaps ? $styleMaps : array();
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
     * @return bool
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    public function addStyle($styleMapId, $styleId)
    {
        $styleMap = $this->getById($styleMapId);
        if ($styleMap) {

            $style = $this->styleManager->getById($styleId);

            if (!$style) {
                throw new Exception('Der Style kann nicht hinzugefügt werden. Er existiert nicht mehr.');
            }

            $styleMap->addStyle($styleId);
            $style->addStyleMap($styleMapId);

            $this->styleManager->save($style);
            return $this->save($styleMap) ? true : false;
        }

        return false;
    }


    /**
     * @param string $styleMapId
     * @param string $styleId
     * @return bool
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    public function removeStyle($styleMapId, $styleId)
    {
        $styleMap = $this->getById($styleMapId);
        if ($styleMap) {

            $style = $this->styleManager->getById($styleId);

            if (!$style) {
                throw new Exception('Der Style kann nicht gelöscht werden. Er gehört nicht zu der Stylemap.');
            }

            $style->removeStyleMapById($styleMapId);
            $styleMap->removeStyleById($styleId);

            $this->styleManager->save($style);
            return $this->save($styleMap) ? true : false;
        }

        return false;
    }
}