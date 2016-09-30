<?php
namespace Mapbender\SearchBundle\Element;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\DataSourceBundle\Element\BaseElement;
use Mapbender\DataSourceBundle\Entity\DataItem;
use Mapbender\SearchBundle\Entity\SearchConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Search
 *
 *
 * @package Mapbender\DataSourceBundle\Element
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class Search extends BaseElement
{
    /**
     * The constructor.
     *
     * @param Application        $application The application object
     * @param ContainerInterface $container   The container object
     * @param Element            $entity
     */
    public function __construct(Application $application, ContainerInterface $container, Element $entity)
    {
        parent::__construct($application, $container, $entity);
    }

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "Search";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Search element";
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbSearch';
    }

    /**
     * @inheritdoc
     */
    static public function getTags()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        $SearchConfig = new SearchConfig();
        return $SearchConfig->toArray();
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\SearchBundle\Element\Type\SearchAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'SearchBundle:ElementAdmin:search.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return /** @lang XHTML */
            '<div
                id="' . $this->getId() . '"
                class="mb-element mb-element-search modal-body"
                title="' . _($this->getTitle()) . '"></div>';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array(
            'css'   => array(
                '@MapbenderSearchBundle/Resources/styles/search.element.scss'
            ),
            'js'    => array(
                '@MapbenderSearchBundle/Resources/public/search.element.js'
            ),
            'trans' => array(
                'MapbenderSearchBundle:Element:search.json.twig'
            )
        );
    }

    /**
     * @return SearchConfig
     */
    public function getConfig()
    {
        return new SearchConfig(parent::getConfiguration());
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        return $this->getConfig()->toArray();
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        /** @var DataItem $dataItem */
        /** @var $requestService Request */
        /** @var Registry $doctrine */
        /** @var Connection $connection */
        $configuration   = $this->getConfig();
        $requestService  = $this->container->get('request');
        $defaultCriteria = array();
        $payload         = json_decode($requestService->getContent(), true);
        $request         = $requestService->getContent() ? array_merge($defaultCriteria, $payload ? $payload : $_REQUEST) : array();
        $results         = array();
        $queryManager    = $this->container->get("mapbender.query.manager");

        switch ($action) {

            // TODO:  check and validate
            case 'validate':
            case 'check...':
            case 'save':
                break;
        }

        return new JsonResponse($results);
    }

    /**
     * Execute query by ID
     *
     * @param $id
     * @return array
     */
    protected function executeQuery($id)
    {
        $configuration = $this->getConfig();
        $query         = $this->getQuery($id);
        $sql           = $query->getAttribute($configuration->sqlFieldName);
        $doctrine      = $this->container->get("doctrine");
        $connection    = $doctrine->getConnection($query->getAttribute($configuration->connectionFieldName));
        $results       = $connection->fetchAll($sql);
        return $results;
    }

    /**
     * @param $configuration
     * @return \Mapbender\DataSourceBundle\Component\DataStore
     */
    protected function getDataStore(SearchConfig $configuration)
    {
        $dataStoreService = $this->container->get("features");
        return $dataStoreService->get($configuration->source);
    }

    /**
     * Get SQL query by id
     *
     * @param int $id
     * @return DataItem
     */
    protected function getQuery($id)
    {
        return $this->getDataStore($this->getConfig())->getById($id);
    }

}