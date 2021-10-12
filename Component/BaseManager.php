<?php

namespace Mapbender\SearchBundle\Component;

use Doctrine\DBAL\Connection;
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

    /** @var Connection|null */
    protected $connection;

    /** @var HKVStorage|HKVStorageBetter */
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
        $connection = $this->getConnection();
        $sql = 'SELECT value FROM ' . $connection->quoteIdentifier($this->tableName)
             . ' WHERE key = :key ORDER BY id DESC, creationDate DESC LIMIT 1';
        $params = array(
            ':key' => $id,
        );
        $rows = $connection->fetchAll($sql, $params);
        $item = $rows ? $this->create(\json_decode($rows[0]['value'], true)) : null;
        return $item;
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
        $connection = $this->getConnection();
        $sql = 'DELETE FROM ' . $connection->quoteIdentifier($this->tableName) . ' WHERE key = :key';
        $params = array(
            ':key' => $id,
        );
        $rowsAffected = $connection->executeStatement($sql, $params);
        return !!$rowsAffected;
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

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $params = array(
                'path' => $this->path,
            );
            $this->connection = new Connection($params, new \Doctrine\DBAL\Driver\PDO\SQLite\Driver());
        }
        return $this->connection;
    }
}
