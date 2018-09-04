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
     * @param StyleManager $styleManager
     * @param string $sqlitePath
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function __construct(ContainerInterface $container, StyleManager $styleManager, $sqlitePath)
    {
        parent::__construct($container, $sqlitePath);
        $this->styleManager = $styleManager;
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
     * @return StyleMap
     */
    public function save($styleMap)
    {
        $styleMaps                       = $this->getAll();
        $styleMaps[ $styleMap->getId() ] = $styleMap;
        $this->db->saveData($this->tableName, $styleMaps, null, null, $this->getUserId());
        return $styleMap;
    }

    /**
     * Get StyleMap by id
     *
     * @param int  $id
     * @return StyleMap|null
     */
    public function getById($id)
    {
        $styleMaps = $this->getAll();
        return isset($styleMaps[ $id ]) ? $styleMaps[ $id ] : null;

    }

    /**
     * List all StyleMaps
     *
     * @return \Mapbender\SearchBundle\Entity\StyleMap[]|null
     */
    public function getAll()
    {
        /**@var StyleMap[] $styleMaps*/
        $styleMaps = $this->db->getData($this->tableName, null, null, $this->getUserId());
        return $styleMaps ? $styleMaps : array();
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