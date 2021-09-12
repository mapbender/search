<?php

namespace Mapbender\SearchBundle\Element;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Mapbender\CoreBundle\Entity;
use Mapbender\SearchBundle\Component\FeatureTypeFactory;
use Mapbender\SearchBundle\Component\HKVStorageBetter;
use FOM\CoreBundle\Component\ExportResponse;
use Mapbender\DataSourceBundle\Component\DataStore;
use Mapbender\DataSourceBundle\Component\DataStoreService;
use Mapbender\DataSourceBundle\Component\FeatureTypeService;
use Mapbender\DataSourceBundle\Element\BaseElement;
use Mapbender\SearchBundle\Component\QueryManager;
use Mapbender\SearchBundle\Component\StyleManager;
use Mapbender\SearchBundle\Component\StyleMapManager;
use Mapbender\SearchBundle\Entity\QuerySchema;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class Search

 * @package Mapbender\SearchBundle\Element
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class Search extends BaseElement
{
    public static function getClassTitle()
    {
        return 'Search';
    }

    public static function getClassDescription()
    {
        return 'Object search element';
    }

    /** @inheritdoc */
    public function getAssets()
    {
        return array(
            'js' => array(
                '../../vendor/blueimp/jquery-file-upload/js/jquery.fileupload.js',
                '../../vendor/blueimp/jquery-file-upload/js/jquery.iframe-transport.js',
                '/components/jquery-context-menu/jquery-context-menu-built.js',
                '/components/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js',
                'OpenLayerHelper.js',
                'feature-style-editor.js',
                'style-map-manager.js',
                'query-result-title-bar-view.js',
                'query-result-view.js',
                'query-manager.js',
                'mapbender.element.search.js',
            ),
            'css' => array(
                'sass/element/search.scss',
            ),
            'trans' => array(
                'MapbenderSearchBundle:Element:search.json.twig',
            ),
        );
    }

    /** @inheritdoc */
    public static function getDefaultConfiguration()
    {
        return array(
            'target'       => null,
            'featureTypes' => array(),
            'debug'        => false,
            'title'        => 'Search',
            'schemas' => array(),
        );
    }

    /**
     * Get the element configuration form type.
     *
     * Override this method to provide a custom configuration form instead of
     * the default YAML form.
     *
     * @return string Administration type class name
     */
    public static function getType()
    {
        return null;
    }

    /**
     * @return null
     */
    public static function getFormTemplate()
    {
        return null;
    }

    /**
     * Prepare form items for each scheme definition
     * Optional: get featureType by name from global context.
     *
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function getConfiguration()
    {
        $configuration = $this->entity->getConfiguration();
        $configuration['debug']   = isset($configuration['debug']) ? $configuration['debug'] : false;
        return $configuration;
    }

    public function handleHttpRequest(Request $request)
    {
        $action = $request->attributes->get('action');
        // action seems to come in lower-case anyway, might be browser dependent
        $action = strtolower($action);
        switch ($action) {
            case 'schemas/list':
                return $this->listSchemasAction();
            case 'query/fetch':
                return new JsonResponse($this->fetchQueryAction($this->getRequestData()));
            case 'query/check':
                return $this->checkQueryAction($this->getRequestData());
            case 'export':
                return $this->exportAction($this->getRequestData());
            case 'queries/list':
            case 'query/save':
            case 'query/remove':
                $saveDataKey = 'query';
                $repository = $this->getQueryManager();
                break;
            case 'style/list':
            case 'style/save':
                $saveDataKey = 'style';
                $repository = $this->getStyleManager();
                break;
            case 'stylemap/list':
            case 'stylemap/save':
                $saveDataKey = 'styleMap';
                $repository = $this->getStyleMapManager();
                break;
            default:
                throw new BadRequestHttpException("Invalid action " . var_export($action, true));
        }
        switch ($action) {
            case 'queries/list':
            case 'style/list':
            case 'stylemap/list':
                return new JsonResponse(array(
                    'list' => array_reverse($repository->getAll(), true)
                ));
            case 'query/remove':
                $requestData = $this->getRequestData();
                return new JsonResponse(array(
                    'result' => $repository->remove($requestData['id']),
                ));
            case 'query/save':
            case 'style/save':
            case 'stylemap/save':
                $requestData = $this->getRequestData();
                $entity = $repository->createFiltered($requestData[$saveDataKey]);
                $entity->setUserId($this->getUserId());
                $repository->save($entity);
                // @todo: fix this inconsistency
                $responseDataKey = ($saveDataKey == 'query') ? 'entity' : $saveDataKey;
                return new JsonResponse(array(
                    $responseDataKey => $entity,
                ));
            default:
                break;
        }
        throw new BadRequestHttpException("Invalid action " . var_export($action, true));
    }

    /**
     * Export results
     *
     * @param $request
     * @return ExportResponse
     */
    public function exportAction($request)
    {
        $ids             = isset($request['ids']) && is_array($request['ids']) ? $request['ids'] : array();
        $queryManager = $this->getQueryManager();
        $query           = $queryManager->getById($request['queryId']);
        $ftConfig = $this->getFeatureTypeConfigForSchema($this->entity, $query->getSchemaId());
        $featureType = $this->getFeatureTypeFromConfig($ftConfig);
        $config = $ftConfig['export'];
        $connection      = $featureType->getConnection();
        $maxResults      = isset($config["maxResults"]) ? $config["maxResults"] : 10000; // TODO: Set max results in export
        $fileName        = $query->getName() . " " . date('Y:m:d H:i:s');

        if (!count($ids)) {
            $fields  = $featureType->getFields();
            $sql = $queryManager->buildSql($featureType, $query, false, $fields);
            $results = $connection->fetchAll($sql . " LIMIT " . $maxResults);
            $rows    = $featureType->export($results);
        } else {
            $rows = $featureType->getByIds($ids, false);
            $rows = $featureType->export($rows);
        }

        return new ExportResponse($rows, $fileName, $request["type"]);
    }

    /**
     * @return JsonResponse
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function listSchemasAction()
    {
        $result                  = array();
        $featureTypeManager      = $this->getFeatureTypeService();
        $featureTypeDeclarations = $featureTypeManager->getFeatureTypeDeclarations();
        $schemas                 = $this->getSchemas();

        foreach ($schemas as $schemaId => $schema) {
            $featureTypeName = $schema->getFeatureType();
            $declaration     = $featureTypeDeclarations[ $featureTypeName ];
            $title           = isset($declaration['title']) ? $declaration['title'] : ucfirst($featureTypeName);
            $fields          = $schema->getFields();

            foreach ($fields as &$fieldDescription) {
                if (isset($fieldDescription["sql"])) {
                    /** @var Connection $dbalConnection */
                    $connectionName = isset($fieldDescription["connection"]) ? $fieldDescription["connection"] : "default";
                    $dbalConnection = $this->container->get("doctrine.dbal.{$connectionName}_connection");
                    $options        = array();

                    foreach ($dbalConnection->fetchAll($fieldDescription["sql"]) as $row) {
                        $options[ current($row) ] = current($row);
                    }

                    $fieldDescription["options"] = $options;
                    unset($fieldDescription["connection"]);
                    unset($fieldDescription["sql"]);
                }
            }

            $result[ $schemaId ] = array(
                'title'       => $title,
                'fields'      => $fields,
                'print' => !empty($declaration['print']) ? $declaration['print'] : null,
                'featureType' => $featureTypeName
            );
        }

        ksort($result);

        return new JsonResponse(array(
            'list' => $result,
        ));
    }

    /**
     * Removes a Style Entity ID from StyleMap Entity
     *
     * @param $request
     * @return JsonResponse
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function removeStyleFromStyleMapAction($request)
    {
        $styleManager = $this->getStyleMapManager();
        $styleMapId   = $request['styleMapId'];
        $styleId      = $request['styleId'];
        return new JsonResponse(array(
            'result' => $styleManager->removeStyle($styleMapId, $styleId)
        ));
    }


    /**
     * Add a Style Entity ID to StyleMap Entity
     *
     * @param $request
     * @return JsonResponse
     */
    public function addStyleToStylemapAction($request)
    {
        $styleMapManager = $this->getStyleMapManager();
        $styleMapId      = isset($request["stylemapid"]) ? $request["stylemapid"] : "UNDEFINED";
        $styleId         = isset($request["styleid"]) ? $request["styleid"] : "UNDEFINED";
        $style           = $styleMapManager->addStyle($styleMapId, $styleId);

        return new JsonResponse(array(
            'stylemap' => HKVStorageBetter::encodeValue($style)
        ));
    }

    /**
     * List queries
     *
     * @param $request
     * @return JsonResponse
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function checkQueryAction($request)
    {
        $queryManager = $this->getQueryManager();
        $query        = $queryManager->create($request['query']);

        try {
            $featureType = $this->getFeatureTypeForSchema($this->entity, $query->getSchemaId());
            $check = $queryManager->check($featureType, $query, $request['intersectGeometry'], $request['srid']);
        } catch (DBALException $e) {
            $message = $e->getMessage();
            if (strpos($message, 'ERROR:')) {
                preg_match("/\\s+ERROR:\\s+(.+)/", $message, $found);
                $message = ucfirst($found[1]) . ".";
            }
            $check = array(
                'errorMessage' => $message,
            );
        }

        return new JsonResponse($check);
    }


    /**
     *
     * @param $request
     * @return \Mapbender\DataSourceBundle\Entity\Feature[]
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function fetchQueryAction($request)
    {
        $queryManager = $this->getQueryManager();
        $query = $queryManager->getById($request['query']['id']);

        $schemaConfig = $this->getSchemaConfigByName($this->entity, $query->getSchemaId());
        $featureType = $this->getFeatureTypeForSchema($this->entity, $query->getSchemaId());

        try {
            $maxResults = isset($schemaConfig['maxResults']) ? $schemaConfig['maxResults'] : 500;
            $request['maxResults'] = $maxResults;
            $results               = $queryManager->fetchQuery($featureType, $query, $request);
            $count                 = count($results["features"]);


            if ($count == $maxResults) {
                $results["infoMessage"] = "Mehr als $maxResults Treffer gefunden, $maxResults Treffer angezeigt. \nGgf. an Kollegen mit FLIMAS-Desktop wenden.";
            }

            return $results;
        } catch (DBALException $e) {
            $message = $e->getMessage();
            if (strpos($message, 'ERROR:')) {
                preg_match("/\\s+ERROR:\\s+(.+)/", $message, $found);
                $message = ucfirst($found[1]) . ".";
            }
            $check = array(
                'errorMessage' => $message,
            );
        }

        return $check;
    }

    /**
     * Get data store
     *
     * @param $request
     * @return JsonResponse
     */
    public function getDatastoreAction($request)
    {
        $results = array();
        // TODO: get request ID and check
        if (!isset($request['id']) || !isset($request['dataItemId'])) {
            $results = array(
                array('errors' => array(
                    array('message' => "datastore/get: id or dataItemId not defined!")
                ))
            );
        }

        $id           = $request['id'];
        $dataItemId   = $request['dataItemId'];
        $dataStore    = $this->getDataStoreById($id);
        $dataItem = $dataStore->getById($dataItemId);
        $dataItemData = null;
        if ($dataItem) {
            $dataItemData = $dataItem->toArray();
            $results      = $dataItemData;
        }
        return new JsonResponse($results);
    }

    /**
     * Save data store
     *
     * @param $request
     * @return JsonResponse
     */
    public function saveDatastoreAction($request)
    {
        $id          = $request['id'];
        $dataItem    = $request['dataItem'];
        $dataStore   = $this->getDataStoreById($id);
        $uniqueIdKey = $dataStore->getDriver()->getUniqueId();
        if (empty($request['dataItem'][ $uniqueIdKey ])) {
            unset($request['dataItem'][ $uniqueIdKey ]);
        }
        return new JsonResponse($dataStore->save($dataItem));
    }

    /**
     * Remove data store
     *
     * @param $request
     * @return JsonResponse
     */
    public function removeDatastoreAction($request)
    {
        $id                = $request['id'];
        $dataStore         = $this->getDataStoreById($id);
        $uniqueIdKey       = $dataStore->getDriver()->getUniqueId();
        $dataItemId        = $request['dataItem'][ $uniqueIdKey ];
        $results["result"] = $dataStore->remove($dataItemId);
        return new JsonResponse(array(
            'id'         => $id,
            'removed'    => $dataStore->remove($dataItemId),
            'dataItemId' => $dataItemId
        ));
    }

    /**
     * Get element schemas
     *
     * @return QuerySchema[]
     */
    protected function getSchemas()
    {
        $configuration = $this->getConfiguration();
        $schemas       = array();
        foreach ($configuration["schemas"] as $schemaDefinition) {
            $schemas[] = new QuerySchema($schemaDefinition);
        }
        return $schemas;
    }

    /**
     * @return QueryManager
     */
    protected function getQueryManager()
    {
        /** @var QueryManager $queryManager */
        $queryManager = $this->container->get('mapbender.search.query.manager');
        return $queryManager;
    }

    protected function getFeatureTypeConfigForSchema(Entity\Element $element, $schemaName)
    {
        $schemaConfig = $this->getSchemaConfigByName($element, $schemaName);
        if (\is_string($schemaConfig['featureType'])) {
            $declarations = $this->getFeatureTypeService()->getFeatureTypeDeclarations();
            return $declarations[$schemaConfig['featureType']];
        } else {
            return $schemaConfig['featureType'];
        }
    }

    protected function getSchemaConfigByName(Entity\Element $element, $schemaName)
    {
        $config = $element->getConfiguration() + array('schemas' => array());
        if (\is_numeric($schemaName)) {
            // Uh-oh. Schema "id" passed in.
            $names = \array_keys($config['schemas']);
            $schemaName = $names[$schemaName];
        }
        return $config['schemas'][$schemaName];
    }

    /**
     * @param Entity\Element $element
     * @param string $schemaName
     * @return \Mapbender\DataSourceBundle\Component\FeatureType
     */
    protected function getFeatureTypeForSchema(Entity\Element $element, $schemaName)
    {
        return $this->getFeatureTypeFromConfig($this->getFeatureTypeConfigForSchema($element, $schemaName));
    }

    /**
     * @param array $config
     * @return \Mapbender\DataSourceBundle\Component\FeatureType
     */
    protected function getFeatureTypeFromConfig(array $config)
    {
        /** @var FeatureTypeFactory|\Mapbender\DataSourceBundle\Component\Factory\FeatureTypeFactory $factory */
        $factory = $this->container->get('mapbender.search.featuretype_factory');
        return $factory->createFeatureType($config);
    }

    /**
     * @return FeatureTypeService
     */
    protected function getFeatureTypeService()
    {
        /** @var FeatureTypeService $service */
        $service = $this->container->get('mapbender.search.featuretype_registry');
        return $service;
    }

    /**
     * @return StyleManager
     */
    protected function getStyleManager()
    {
        /** @var StyleManager $service */
        $service = $this->container->get('mapbender.search.style.manager');
        return $service;
    }

    /**
     * @return StyleMapManager
     */
    protected function getStyleMapManager()
    {
        /** @var StyleMapManager $service */
        $service = $this->container->get('mapbender.search.stylemap.manager');
        return $service;
    }

    /**
     * @param string $id
     * @return DataStore
     */
    protected function getDataStoreById($id)
    {
        /** @var DataStoreService $dataStoreService */
        $dataStoreService = $this->container->get('data.source');
        return $dataStoreService->get($id);
    }
}
