<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Entity\HKV;
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
     * @param array $args
     * @return StyleMap
     */
    public function createStyleMap($args)
    {
        $styleMap = new StyleMap();
        $style    = $this->createStyle($args);
        $styleMap->addStyle($style);
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
     * save query
     *
     * @param StyleMap $styleMap
     * @return HKV
     */
    public function save(StyleMap $styleMap, $scope = null, $parentId = null)
    {
        $list = $this->listStyleMaps();

        $list[] = $styleMap;
        $userId = $styleMap->getUserId();
        $result = $this->db->saveData($this->tableName, $list, $scope, $parentId, $userId);

        return $result;
    }


    /**
     * Get StyleMap by id
     *
     * @param int    $id
     * @param string $userId
     * @return StyleMap|null
     */
    public function getById($id, $userId = SecurityContext::USER_ANONYMOUS_ID)
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
        $list       = $this->listStyleMaps();
        $isUpdating = $list != null;

        if ($isUpdating) {
            return $this->updateInternal($list, $args);
        } else {
            return $this->create($args);
        }

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

        return $this->createStyleMap($args);
    }

    /**
     * @param  StyleMap[] $list
     * @param  array      $args
     * @return array
     */
    private function updateInternal($list, $args)
    {
        $styleMaps = array();
        foreach ($list as $key => $value) {
            $hasStyleMap = $value->getId() == $args["id"];
            if ($hasStyleMap) {
                /** @var  StyleMap $value */
                $value->fill($args);
                $styleMaps[] = $this->save($value);
            }

        }

        return $styleMaps;
    }


}