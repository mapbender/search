<?php

namespace Mapbender\SearchBundle\Element;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Mapbender\CoreBundle\Entity;
use Mapbender\SearchBundle\Component\FeatureTypeFactory;
use Mapbender\SearchBundle\Component\HKVStorageBetter;
use FOM\CoreBundle\Component\ExportResponse;
use Mapbender\DataSourceBundle\Element\BaseElement;
use Mapbender\SearchBundle\Component\QueryManager;
use Mapbender\SearchBundle\Component\StyleManager;
use Mapbender\SearchBundle\Component\StyleMapManager;
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
                'mb.search.*'
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

    public function getWidgetName()
    {
        return 'mapbender.mbSearch';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderSearchBundle:Element:search.html.twig';
    }

    public function getFrontendTemplateVars()
    {
        return array();
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
                return new JsonResponse($this->fetchQueryAction($request));
            case 'query/check':
                return $this->checkQueryAction($request);
            case 'export':
                return $this->exportAction($request);
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
                $requestData = \json_decode($request->getContent(), true);
                return new JsonResponse(array(
                    'result' => $repository->remove($requestData['id']),
                ));
            case 'query/save':
            case 'style/save':
            case 'stylemap/save':
                $requestData = $this->expandArrayInputs(\json_decode($request->getContent(), true));
                $entity = $repository->createFiltered($requestData[$saveDataKey]);
                $entity->setUserId($repository->getUserId());
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
     * @param Request $request
     * @return ExportResponse
     */
    public function exportAction(Request $request)
    {
        $ids = $request->request->get('ids', array());

        $queryManager = $this->getQueryManager();
        $query = $queryManager->getById($request->request->get('queryId'));
        $ftConfig = $this->getFeatureTypeConfigForSchema($this->entity, $query->getSchemaId());
        $featureType = $this->getFeatureTypeFromConfig($ftConfig);
        $config = $ftConfig['export'];
        $connection      = $featureType->getConnection();
        $maxResults = isset($config["maxResults"]) ? $config["maxResults"] : 10000; // TODO: Set max results in export
        $fileName        = $query->getName() . " " . date('Y:m:d H:i:s');

        $sql = $queryManager->buildSql($featureType, $query);
        if ($ids) {
            $sql .= ' AND ' . $connection->quoteIdentifier($featureType->getUniqueId()) . ' IN (' . implode(', ', array_map('intval', $ids)) . ')';
        } else {
            $sql .= ' LIMIT ' . $maxResults;
        }
        $dbRows = $connection->fetchAll($sql);
        $rows = array();
        foreach ($dbRows as $dbRow) {
            if (!empty($config['fields'])) {
                $exportRow = array();
                foreach ($config['fields'] as $cellTitle => $cellExpression) {
                    $exportRow[$cellTitle] = $this->formatExportCell($dbRow, $cellExpression);
                }
                $rows[] = $exportRow;
            } else {
                $rows[] = $dbRow;
            }
        }
        return new ExportResponse($rows, $fileName, $request->request->get('type'));
    }

    protected static function formatExportCell($row, $code)
    {
        // @todo: stop using eval already
        $result = null;
        extract($row);
        eval('$result = ' . $code . ';');
        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $result;
    }

    /**
     * @return JsonResponse
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function listSchemasAction()
    {
        $result                  = array();
        $config = $this->entity->getConfiguration();
        $schemaNames = \array_keys($config['schemas']);

        foreach ($schemaNames as $schemaId) {
            $schemaConfig = $this->getSchemaConfigByName($this->entity, $schemaId);
            $declaration = $this->getFeatureTypeConfigForSchema($this->entity, $schemaId);
            $fields = $schemaConfig['fields'];

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
                'title' => $declaration['title'],
                'fields'      => $fields,
                'print' => !empty($declaration['print']) ? $declaration['print'] : null,
                'featureType' => $schemaConfig['featureType'],
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
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function checkQueryAction(Request $request)
    {
        $requestData = \json_decode($request->getContent(), true);
        $queryManager = $this->getQueryManager();
        $query = $queryManager->create($requestData['query']);

        try {
            $featureType = $this->getFeatureTypeForSchema($this->entity, $query->getSchemaId());
            $check = $queryManager->check($featureType, $query, $requestData['intersectGeometry'], $requestData['srid']);
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
     * @param Request $request
     * @return array
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function fetchQueryAction(Request $request)
    {
        $queryManager = $this->getQueryManager();
        $query = $queryManager->getById($request->query->get('queryId'));

        $schemaConfig = $this->getSchemaConfigByName($this->entity, $query->getSchemaId());
        $featureType = $this->getFeatureTypeForSchema($this->entity, $query->getSchemaId());


        try {
            $maxResults = $schemaConfig['maxResults'];
            $params = array_filter(array(
                'maxResults' => $maxResults,
                'srid' => $request->query->get('srid'),
                'intersectGeometry' => $request->query->get('intersectGeometry'),
            ));
            $results = $queryManager->fetchQuery($featureType, $query, $params);
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
            $declarations = $this->container->getParameter('featureTypes');
            $ftConfig = $declarations[$schemaConfig['featureType']];
            if (empty($ftConfig['title'])) {
                $ftConfig['title'] = ucfirst($schemaConfig['featureType']);
            }
        } else {
            $ftConfig = $schemaConfig['featureType'];
            if (empty($ftConfig['title'])) {
                $ftConfig['title'] = \is_numeric($schemaName) ? "#{$schemaName}" : ucfirst($schemaName);
            }
        }
        return $ftConfig;
    }

    protected function getSchemaConfigByName(Entity\Element $element, $schemaName)
    {
        $config = $element->getConfiguration() + array('schemas' => array());
        if (\is_numeric($schemaName)) {
            // Uh-oh. Schema "id" passed in.
            $names = \array_keys($config['schemas']);
            $schemaName = $names[$schemaName];
        }
        $defaults = array(
            'maxResults' => 500,
            'fields' => array(),
        );
        return $config['schemas'][$schemaName] + $defaults;
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
        return $factory->fromConfig($config);
    }

    protected function expandArrayInputs($data)
    {
        $nested = array();
        foreach ($data as $key => $value) {
            if (\is_array($value)) {
                $value = $this->expandArrayInputs($value);
            }
            $matches = array();
            if (\preg_match('#^([^[]*)\[(.*?)\]$#', $key, $matches)) {
                $nested += array($matches[1] => array());
                $nested[$matches[1]][$matches[2]] = $value;
            } else {
                $nested[$key] = $value;
            }
        }
        return $nested;
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
}
