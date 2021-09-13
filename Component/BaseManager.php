<?php

namespace Mapbender\SearchBundle\Component;

use FOM\UserBundle\Entity\User;
use Mapbender\SearchBundle\Component\HKVStorageBetter;
use Mapbender\SearchBundle\Entity\UniqueBase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class BaseManager
 *
 * @package Mapbender\SearchBundle\Component
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
abstract class BaseManager implements ManagerInterface
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var HKVStorage */
    protected $db;

    /** @var string tableName */
    protected $tableName;

    /** @var string path */
    protected $path;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param string $path full filename for sqlite table
     */
    public function __construct(TokenStorageInterface $tokenStorage, $path)
    {
        $this->tokenStorage = $tokenStorage;
        $this->path      = $path;
        $baseName = preg_replace('#^([^/]*/)*#', '', $this->path);
        $this->tableName = preg_replace('#\..*$#', '', $baseName);
        $this->createDB();
    }

    /**
     * @param mixed[] $data
     * @return UniqueBase
     */
    abstract public function create($data);

    /**
     * List all records
     *
     * @return UniqueBase[]
     * @internal param int $id
     */
    public function getAll()
    {
        /** @var UniqueBase[] $list */
        $list = $this->db->getData($this->tableName, null, null, $this->getUserId());
        return $list ? $list : array();
    }

    /**
     * @param int $id
     * @return UniqueBase|null
     */
    public function getById($id)
    {
        $allRecords = $this->getAll();
        return isset($allRecords[$id]) ? $allRecords[$id] : null;

    }

    /**
     * @param UniqueBase $entity
     * @return UniqueBase
     */
    public function save($entity)
    {
        if (!$entity->getId()) {
            $entity->setId($this->generateUUID());
        }
        $all = $this->getAll();
        $id = $entity->getId();
        $all[$id] = $entity;
        $this->db->saveData($this->tableName, $all, null, null, $this->getUserId());

        return $entity;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function remove($id)
    {
        $list = $this->getAll();
        $wasMissed = isset($list[$id]);
        unset($list[$id]);

        $this->db->saveData($this->tableName, $list, null, null, $this->getUserId());

        return $wasMissed;
    }

    /**
     * @param $data
     * @return UniqueBase
     */
    public function createFiltered($data)
    {
        $filtered = $this->filterFields($data);
        return $this->create($filtered);
    }

    /**
     * @return string[] field names that are not settable by the user
     */
    protected function getBlacklistedFields()
    {
        return array(
        );
    }

    /**
     * Remove blacklisted fields
     *
     * @param mixed[] $data
     * @return mixed[]
     */
    protected function filterFields($data)
    {
        foreach ($this->getBlacklistedFields() as $deniedFieldName) {
            unset($data[$deniedFieldName]);
        }
        return $data;
    }


    /**
     * Drop database
     *
     * @return bool
     */
    public function dropDatabase()
    {
        return $this->db->destroy();
    }


    /**
     * @return string
     */
    protected function generateUUID()
    {
        return uniqid("", true);
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token || ($token instanceof AnonymousToken)) {
            return 0;   // BaseElement continuity hack
        } else {
            $user = $token->getUser();
            if ($user && \is_object($user)) {
                // @todo: LDAP user id vs user name handling could easily be handled here
                if ($user instanceof User) {
                    return $user->getId();
                }
            }
            return $token->getUsername();
        }
    }

    /**
     */
    public function createDB()
    {
        $this->db = new HKVStorageBetter($this->path, $this->tableName);
    }
}
