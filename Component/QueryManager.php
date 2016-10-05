<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
use Eslider\Entity\HKVSearchFilter;
use FOM\UserBundle\Entity\User;
use Mapbender\ConfiguratorBundle\Component\BaseComponent;
use Mapbender\SearchBundle\Entity\Query;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QueryManager
 *
 * @package Mapbender\SearchBundle\Element
 */
class QueryManager extends BaseComponent
{
    /** @var HKVStorage */
    protected $db;


    /* @var Configuration */
    protected $configuration;

    /* @var User */
    protected $user;


    /**
     * Configurator constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;

        $kernel    = $this->container->get('kernel');
        $path      = $kernel->getRootDir() . "/config/queries.sqlite";
        $tableName = "queries";
        $this->db  = new HKVStorage($path, $tableName);

        parent::__construct($container);
    }

    /**
     * Save query
     *
     * @param Query $query
     */
    public function save(Query $query)
    {
        // $user   = $this->getUser();
        // $userId = $user->getId();
        // $query->setUserId($userId);

        $list   = $this->listQueries();
        $list[] = $query;
        return $this->db->saveData("queries", $list);
    }


    /**
     * Get query by id
     *
     * @param int $id
     */
    public function getById($id)
    {
        $filterData = array("id" => $id);
        $filter     = new HKVSearchFilter($filterData);
        return $this->db->get($filter);
    }


    /**
     * List all Queries
     *
     * @param int $id
     */
    public function listQueries()
    {
        if (isset($this->user)) {
            $user   = $this->user;
            $userId = $user->getId();
            $list   = $this->db->getParentByChildrenId($userId);
            return $list;
        }

        return array();
    }

    /**
     * Remove Query with certain $queryId
     *
     * @param $queryId
     */

    public function remove($queryId)
    {

        $queries = $this->listQueries();

        foreach ($queries as $key => $value) {
            if ($value->id == $queryId) {
                unset($queries[ $key ]);
                $this->db->saveData("queries", $queries);
                return;
            }
        }
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param Configuration $configuration
     * @return QueryManager
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @param User $user
     * @return QueryManager
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

}