<?php
/**
 * Created by PhpStorm.
 * User: jheinrich
 * Date: 13.03.19
 * Time: 16:59
 */

namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
use Eslider\Entity\HKVSearchFilter;
use Mapbender\SearchBundle\Entity\HKVBetter;

class HKVStorageBetter extends HKVStorage
{
    /**
     * Get by HKV filter
     *
     * @param HKV|HKVSearchFilter $filter
     * @return HKV
     */
    public function get(HKVSearchFilter $filter)
    {
        $db = $this->db();

        if (!$db->hasTable($this->tableName)) {
            return new HKVBetter();
        }

        $query    = $this->createQuery($filter);
        $dataItem = new HKVBetter($db->fetchRow($query));

        if ($dataItem->isArray() || $dataItem->isObject()) {
            $dataItem->setValue(static::decodeValue($dataItem->getValue()));
        }

        if ($filter->shouldFetchChildren()) {
            $children = $this->getChildren($dataItem->getId(), true, $filter->getScope(), $filter->getUserId());
            $dataItem->setChildren($children);
        }
        return $dataItem;
    }

    /**
     * @param string      $key
     * @param null|string $scope
     * @param null|int    $parentId
     * @param null|int    $userId
     * @return HKV
     */
    public function saveData($key, $value, $scope = null, $parentId = null, $userId = null)
    {
        $hkv     = new HKVBetter();
        $type    = gettype($value);
        $isArray = is_array($value);
        $hkv->setKey($key);
        $hkv->setParentId($parentId);
        $hkv->setScope($scope);
        $hkv->setType($type);
        $hkv->setUserId($userId);

        if (!$isArray) {
            $hkv->setValue($value);
        }
        if ($type == "object") {
            $hkv->setType(get_class($value));
        }

        $this->save($hkv);

        if ($isArray) {
            $childParentId = $hkv->getId();
            $children      = array();
            foreach ($value as $subKey => $item) {
                $children[] = $this->saveData($subKey, $item, $scope, $childParentId, $userId);
            }
            $hkv->setChildren($children);
        }

        return $hkv;
    }
}
