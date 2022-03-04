<?php

namespace Mapbender\SearchBundle\Element;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\Persistence\ConnectionRegistry;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\DataSourceBundle\Component\FeatureType;
use Mapbender\DataSourceBundle\Component\RepositoryRegistry;
use FOM\CoreBundle\Component\ExportResponse;
use Mapbender\SearchBundle\Component\QueryManager;
use Mapbender\SearchBundle\Component\StyleManager;
use Mapbender\SearchBundle\Component\StyleMapManager;
use Mapbender\SearchBundle\Element\Type\SearchAdminType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class Search

 * @package Mapbender\SearchBundle\Element
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class Search extends AbstractElementService implements ElementHttpHandlerInterface
{
    /** @var ConnectionRegistry */
    protected $connectionRegistry;
    /** @var RepositoryRegistry */
    protected $repositoryRegistry;
    /** @var QueryManager */
    protected $queryManager;
    /** @var StyleManager */
    protected $styleManager;
    /** @var StyleMapManager */
    protected $styleMapManager;
    /** @var array[] */
    protected $featureTypes;

    public function __construct(ConnectionRegistry $connectionRegistry,
                                RepositoryRegistry $repositoryRegistry,
                                QueryManager $queryManager,
                                StyleManager $styleManager,
                                StyleMapManager $styleMapManager,
                                $featureTypes)
    {
        $this->connectionRegistry = $connectionRegistry;
        $this->repositoryRegistry = $repositoryRegistry;
        $this->queryManager = $queryManager;
        $this->styleManager = $styleManager;
        $this->styleMapManager = $styleMapManager;
        $this->featureTypes = $featureTypes;
    }


    public static function getClassTitle()
    {
        return 'Search';
    }

    public static function getClassDescription()
    {
        return 'Object search element';
    }

    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '/components/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js',
                '@MapbenderSearchBundle/Resources/public/FormUtil.js',
                '@MapbenderSearchBundle/Resources/public/feature-style-editor.js',
                '@MapbenderSearchBundle/Resources/public/TableRenderer.js',
                '@MapbenderSearchBundle/Resources/public/query-manager.js',
                '@MapbenderSearchBundle/Resources/public/mapbender.element.search.js',
            ),
            'css' => array(
                '@MapbenderSearchBundle/Resources/public/sass/element/search.scss',
                '/components/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css',
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
            'schemas' => array(),
            'clustering' => array(),
        );
    }

    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbSearch';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderSearchBundle:Element:search.html.twig');
        $view->attributes['class'] = 'mb-element-search';
        return $view;
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
        return SearchAdminType::class;
    }

    /**
     * @return null
     */
    public static function getFormTemplate()
    {
        return false;
    }

    public function getHttpHandler(Element $element)
    {
        return $this;
    }

    public function handleRequest(Element $element, Request $request)
    {
        $action = $request->attributes->get('action');
        // action seems to come in lower-case anyway, might be browser dependent
        $action = strtolower($action);
        switch ($action) {
            case 'init':
                return new JsonResponse(array(
                    'schemas' => $this->getSchemaData($element),
                    'styles' => $this->styleManager->getAll(),
                    'styleMaps' => $this->styleMapManager->getAll(),
                    'queries' => \array_reverse($this->queryManager->getAll(), true),
                ));
            case 'query/fetch':
                return new JsonResponse($this->fetchQueryAction($element, $request));
            case 'query/check':
                return $this->checkQueryAction($element, $request);
            case 'export':
                return $this->exportAction($element, $request);
            case 'query/remove':
                $requestData = \json_decode($request->getContent(), true);
                return new JsonResponse(array(
                    'result' => $this->queryManager->remove($requestData['id']),
                ));
            case 'query/save':
                $saveDataKey = 'query';
                $repository = $this->queryManager;
                break;
            case 'style/save':
                $saveDataKey = 'style';
                $repository = $this->styleManager;
                break;
            case 'stylemap/save':
                $saveDataKey = 'styleMap';
                $repository = $this->styleMapManager;
                break;
            default:
                break;
        }
        switch ($action) {
            case 'query/save':
            case 'style/save':
            case 'stylemap/save':
                $requestData = \json_decode($request->getContent(), true);
                $saveData = $repository->filterFields($requestData[$saveDataKey]);
                $entity = $repository->create($saveData);
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
     * @param Element $element
     * @param Request $request
     * @return ExportResponse
     */
    public function exportAction(Element $element, Request $request)
    {
        $ids = $request->request->get('ids', array());

        $query = $this->queryManager->getById($request->request->get('queryId'));
        $ftConfig = $this->getFeatureTypeConfigForSchema($element, $query->getSchemaId());
        $featureType = $this->getFeatureTypeFromConfig($ftConfig);
        $config = $ftConfig['export'];
        $connection      = $featureType->getConnection();
        $maxResults = isset($config["maxResults"]) ? $config["maxResults"] : 10000; // TODO: Set max results in export
        $fileName        = $query->getName() . " " . date('Y:m:d H:i:s');

        $sql = $this->queryManager->buildSql($featureType, $query);
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
     * @param Element $element
     * @return array
     */
    public function getSchemaData(Element $element)
    {
        $result                  = array();
        $config = $element->getConfiguration();
        $schemaNames = \array_keys($config['schemas']);

        foreach ($schemaNames as $schemaId) {
            $schemaConfig = $this->getSchemaConfigByName($element, $schemaId);
            $declaration = $this->getFeatureTypeConfigForSchema($element, $schemaId);
            $fields = $schemaConfig['fields'];

            foreach ($fields as &$fieldDescription) {
                if (isset($fieldDescription["sql"])) {
                    /** @var Connection $dbalConnection */
                    $connectionName = isset($fieldDescription["connection"]) ? $fieldDescription["connection"] : "default";
                    $dbalConnection = $this->connectionRegistry->getConnection($connectionName);
                    $options        = array();

                    foreach ($dbalConnection->fetchAll($fieldDescription["sql"]) as $row) {
                        $options[ current($row) ] = current($row);
                    }

                    $fieldDescription["options"] = $options;
                    unset($fieldDescription["connection"]);
                    unset($fieldDescription["sql"]);
                }
            }

            $result[$schemaId] = array(
                'title' => $declaration['title'],
                'fields'      => $fields,
                'print' => !empty($declaration['print']) ? $declaration['print'] : null,
                'featureType' => $schemaConfig['featureType'],
            );
        }

        ksort($result);
        return $result;

    }

    /**
     * @param Element $element
     * @param Request $request
     * @return JsonResponse
     */
    public function checkQueryAction(Element $element, Request $request)
    {
        $requestData = \json_decode($request->getContent(), true);
        $query = $this->queryManager->create($requestData['query']);

        try {
            $featureType = $this->getFeatureTypeForSchema($element, $query->getSchemaId());
            $params = array_filter(array(
                'srid' => $requestData['srid'],
                'intersect' => $requestData['intersect'],
            ));
            $check = $this->queryManager->check($featureType, $query, $params);
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (DBALException $e) {
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
     * @param Element $element
     * @param Request $request
     * @return array
     */
    public function fetchQueryAction(Element $element, Request $request)
    {
        $query = $this->queryManager->getById($request->query->get('queryId'));

        $schemaConfig = $this->getSchemaConfigByName($element, $query->getSchemaId());
        $featureType = $this->getFeatureTypeForSchema($element, $query->getSchemaId());


        try {
            $maxResults = $schemaConfig['maxResults'];
            $params = array_filter(array(
                'maxResults' => $maxResults,
                'srid' => $request->query->get('srid'),
                'intersect' => $request->query->get('intersect'),
            ));
            $results = $this->queryManager->fetchQuery($featureType, $query, $params);
            $count                 = count($results["features"]);


            if ($count == $maxResults) {
                $results["infoMessage"] = "Mehr als $maxResults Treffer gefunden, $maxResults Treffer angezeigt. \nGgf. an Kollegen mit FLIMAS-Desktop wenden.";
            }

            return $results;
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (DBALException $e) {
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

    protected function getFeatureTypeConfigForSchema(Element $element, $schemaName)
    {
        $schemaConfig = $this->getSchemaConfigByName($element, $schemaName);
        if (\is_string($schemaConfig['featureType'])) {
            $ftConfig = $this->featureTypes[$schemaConfig['featureType']];
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

    protected function getSchemaConfigByName(Element $element, $schemaName)
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
     * @param Element $element
     * @param string $schemaName
     * @return FeatureType
     */
    protected function getFeatureTypeForSchema(Element $element, $schemaName)
    {
        return $this->getFeatureTypeFromConfig($this->getFeatureTypeConfigForSchema($element, $schemaName));
    }

    /**
     * @param array $config
     * @return FeatureType
     */
    protected function getFeatureTypeFromConfig(array $config)
    {
        /** @var FeatureType $ft */
        $ft = $this->repositoryRegistry->dataStoreFactory($config);
        return $ft;
    }
}
