<?php
namespace Mapbender\SearchBundle\Component;


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
        parent::__construct($container, "styles");
    }

    const SERVICE_NAME = "mapbender.style.manager";


    /**
     * @param $args
     * @return StyleMap
     */
    public function create($args)
    {
        /** @var array $args */
        $styleMap = new StyleMap();
        if (isset($args["styles"])) {
            $styles = $args["styles"];
            foreach ($styles as $key => $arg) {
                $styleMap->addStyle(new Style($arg));
            }
        } else {
            $styleMap->addStyle(new Style($args));
        }

        $styleMap->setId($this->generateUUID());
        $styleMap->setUserId($this->getUserId());
        return $styleMap;
    }


    /**
     * save query
     *
     * @param StyleMap $styleMap
     * @return StyleMap
     */
    public function save(StyleMap $styleMap, $scope = null, $parentId = null)
    {
        $list   = $this->listStyleMaps();
        $list[] = $styleMap;
        $userId = $styleMap->getUserId();
        return $this->db->saveData($this->tableName, $list, $scope, $parentId, $userId);
    }


    /**
     * Get StyleMap by id
     *
     * @param int $id
     * @return StyleMap|null
     */
    public function getById($id, $userId = null)
    {
        $list = $this->listStyleMaps();

        foreach ($list as $key => $value) {
            if ($value->getId() == $id && $value->getUserId() == $userId) {
                return $value;
            }
        }

        return null;
    }


    /**
     * List all StyleMaps
     *
     * @param int $id
     * @return StyleMap[]
     */
    public function listStyleMaps()
    {
        return $this->db->getData($this->tableName, null, null, $this->getUserId());
    }

    /**
     * Remove StyleMap with certain $styleMapId
     *
     * @param $styleMapId
     * @return bool
     */
    public function remove($styleMapId)
    {
        $found = false;

        $styleMaps = $this->listStyleMaps();
        foreach ($styleMaps as $key => $query) {
            if ($query->getId() == $styleMapId) {
                unset($styleMaps[ $key ]);
                $found = true;
            }
        }
        $this->db->saveData($this->tableName, $styleMaps);
        return $found;
    }


    /**
     * Update existing
     *
     * @param array $args
     * @return StyleMap
     */
    public function update($args)
    {
        $list = $this->listStyleMaps();
        foreach ($list as $key => $value) {
            $hasStyleMap = $value->getId() == $args["id"];
            if ($hasStyleMap) {
                /** @var  StyleMap $value * */
                $value->fill($args);
                return $this->save($value);
            }
        }
        return $this->save($this->create($args));
    }


}