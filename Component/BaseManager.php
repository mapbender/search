<?php

namespace Mapbender\SearchBundle\Component;

use Doctrine\DBAL\Connection;
use FOM\UserBundle\Entity\User;
use Mapbender\SearchBundle\Entity\Base;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class BaseManager
 *
 * @package Mapbender\SearchBundle\Component
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
abstract class BaseManager
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var Connection|null */
    protected $connection;

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
    }

    /**
     * @param mixed[] $data
     * @return Base
     */
    abstract public function create(array $data);

    /**
     * List all records
     *
     * @return array[]
     * @internal param int $id
     */
    public function getAll()
    {
        $connection = $this->getConnection();
        $tnq = $connection->quoteIdentifier($this->tableName);
        // Filter out legacy hkv storage per-"key" historization entries
        $distinctIdSql = "SELECT MAX(id) FROM {$tnq} WHERE userId LIKE :userId AND value IS NOT NULL GROUP BY key";
        $sql = "SELECT key, value FROM {$tnq} WHERE id IN ({$distinctIdSql})";
        $params = array(
            ':userId' => $this->getUserId(),
        );
        $items = array();
        foreach ($connection->executeQuery($sql, $params)->fetchAll() as $row) {
            $item = $this->create(\json_decode($row['value'], true));
            $items[$row['key']] = $item->toArray();
        }
        return $items;
    }

    /**
     * @param int $id
     * @return Base|null
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
     * @param Base $entity
     */
    public function save($entity)
    {
        $isNew = !$entity->getId();
        if (!$entity->getId()) {
            $entity->setId($this->generateUUID());
        }
        $connection = $this->getConnection();
        $values = $entity->toArray();
        $updateValues = array(
            'value' => \json_encode($values),
            'creationDate' => time(),
            'userId' => $values['userId'],
        );
        $updateCriteria = array(
            'key' => $values['id'],
        );
        $tnq = $connection->quoteIdentifier($this->tableName);
        if ($isNew) {
            $insertValues = $updateValues + $updateCriteria;
            $connection->insert($tnq, $insertValues);
        } else {
            // Autoclean legacy HkvStorage rows
            $cleanSql = "DELETE FROM {$tnq} WHERE key = :key0"
                      . " AND id != (SELECT id FROM {$tnq} WHERE key = :key1 ORDER BY creationDate DESC LIMIT 1)";
            $connection->update($tnq, $updateValues, $updateCriteria);
            $connection->executeStatement($cleanSql, array(
                // Supply param twice to avoid issues with native prepared statements
                ':key0' => $values['id'],
                ':key1' => $values['id'],
            ));
        }
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
     * @return Connection
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $params = array(
                'path' => $this->path,
            );
            $this->connection = new Connection($params, new \Doctrine\DBAL\Driver\PDO\SQLite\Driver());
            $this->ensureTable($this->connection);
            // @todo: indexes?
        }
        return $this->connection;
    }

    protected function ensureTable(Connection $connection)
    {
        if (!$connection->getSchemaManager()->tablesExist(array($this->tableName))) {
            $sql = 'CREATE TABLE ' . $connection->quoteIdentifier($this->tableName) . ' ('
                 . ' id INTEGER NOT NULL PRIMARY KEY'
                 . ', "key" VARCHAR(31) NOT NULL'
                 . ', "value" BLOB'
                 . ', "creationDate" UNSIGNED INTEGER'
                 . ', "userId" BLOB'
                 . ')'
            ;
            $connection->executeStatement($sql);
        }
    }
}
