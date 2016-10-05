<?php
namespace Mapbender\SearchBundle\Component;

use Eslider\Driver\HKVStorage;
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
        $list   = $this->db->getData("queries");
        $list[] = $query;
        $this->db->saveData("queries", $list);
    }
}