<?php


namespace Mapbender\SearchBundle\Element;


use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use FOM\CoreBundle\Component\ExportResponse;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\DataSourceBundle\Component\FeatureType;
use Mapbender\DataSourceBundle\Component\RepositoryRegistry;
use Mapbender\SearchBundle\Component\BaseManager;
use Mapbender\SearchBundle\Component\ConfigFilter;
use Mapbender\SearchBundle\Component\QueryManager;
use Mapbender\SearchBundle\Component\StyleManager;
use Mapbender\SearchBundle\Component\StyleMapManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchHttpHandler implements ElementHttpHandlerInterface
{
    /** @var ConnectionRegistry */
    protected $connectionRegistry;
    /** @var RepositoryRegistry */
    protected $repositoryRegistry;
    /** @var ConfigFilter */
    protected $configFilter;
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
                                ConfigFilter $configFilter,
                                QueryManager $queryManager,
                                StyleManager $styleManager,
                                StyleMapManager $styleMapManager,
                                $featureTypes)
    {
        $this->connectionRegistry = $connectionRegistry;
        $this->repositoryRegistry = $repositoryRegistry;
        $this->configFilter = $configFilter;
        $this->queryManager = $queryManager;
        $this->styleManager = $styleManager;
        $this->styleMapManager = $styleMapManager;
        $this->featureTypes = $featureTypes;
    }

    public function handleRequest(Element $element, Request $request)
    {
        try {
            return $this->dispatchRequest($element, $request) ?: new JsonResponse(null, Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            while ($e->getPrevious()) {
                $e = $e->getPrevious();
            }
            $message = explode("\n", $e->getMessage())[0];
            $message = \preg_replace('#^SQLSTATE[^:]*:#', '', $message);
            $message = \preg_replace('#^.*?ERROR:\s*#', '', $message);
            return new JsonResponse(null, Response::HTTP_INTERNAL_SERVER_ERROR, array(
                'X-Error-Message' => ucfirst(preg_replace('#\s+#', ' ', $message)),
            ));
        }
    }

    protected function dispatchRequest(Element $element, Request $request)
    {
        switch ($request->attributes->get('action')) {
            case 'init':
                return new JsonResponse(array(
                    'schemas' => $this->getSchemaData($element),
                    'styles' => $this->styleManager->getAll(),
                    'styleMaps' => $this->styleMapManager->getAll(),
                    'queries' => \array_reverse($this->queryManager->getAll(), true),
                ));
            case 'query/fetch':
                return $this->fetchQueryAction($element, $request);
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
                return $this->dispatchSave($this->queryManager, $request);
            case 'style/save':
                return $this->dispatchSave($this->styleManager, $request);
            case 'stylemap/save':
                return $this->dispatchSave($this->styleMapManager, $request);
            default:
                return null;
        }
    }

    /**
     * @param Element $element
     * @return array
     */
    protected function getSchemaData(Element $element)
    {
        $result = array();
        $config = $element->getConfiguration();
        $schemaNames = \array_keys($config['schemas']);

        foreach ($schemaNames as $schemaId) {
            $schemaConfig = $this->configFilter->getSchemaConfigByName($element, $schemaId);
            $ftConfig = $this->configFilter->getFeatureTypeConfigForSchema($element, $schemaId);
            /** @var Connection $connection */
            $connection = $this->connectionRegistry->getConnection($ftConfig["connection"]);
            $result[$schemaId] = array(
                'title' => $ftConfig['title'],
                'fields' => $this->configFilter->expandSqlOptions($connection, $schemaConfig['fields']),
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
    protected function checkQueryAction(Element $element, Request $request)
    {
        $requestData = \json_decode($request->getContent(), true);

        $ftConfig = $this->configFilter->getFeatureTypeConfigForSchema($element, $requestData['query']['schemaId']);
        $featureType = $this->repositoryRegistry->dataStoreFactory($ftConfig);
        $t0 = microtime(true);
        $count = $featureType->count(array_filter(array(
            'srid' => $requestData['srid'],
            'intersect' => $requestData['intersect'],
            'where' => $this->formatFetchConditionsSql($featureType->getConnection(), $requestData['query']['conditions']),
        )));
        $tEnd = microtime(true);
        return new JsonResponse(array(
            'count' => $count,
            'executionTime' => round(($tEnd - $t0) * 1000) . 'ms',
        ));
    }

    /**
     * @param Element $element
     * @param Request $request
     * @return JsonResponse
     */
    protected function fetchQueryAction(Element $element, Request $request)
    {
        $query = $this->queryManager->getById($request->query->get('queryId'));

        $ftConfig = $this->configFilter->getFeatureTypeConfigForSchema($element, $query->getSchemaId());
        /** @var FeatureType $featureType */
        $featureType = $this->repositoryRegistry->dataStoreFactory($ftConfig);

        $maxResults = 500;
        $features = $featureType->search(array_filter(array(
            'maxResults' => $maxResults,
            'srid' => $request->query->get('srid'),
            'intersect' => $request->query->get('intersect'),
            'where' => $this->formatFetchConditionsSql($featureType->getConnection(), $query->getConditions()),
        )));
        $responseData = array(
            'features' => array(),
        );
        foreach ($features as $feature) {
            $responseData['features'][] = array(
                'id' => $feature->getId(),
                'geometry' => $feature->getGeom(),
                'properties' => $feature->getAttributes(),
            );
        }

        if (count($features) >= $maxResults) {
            $responseData["infoMessage"] = "Mehr als $maxResults Treffer gefunden, $maxResults Treffer angezeigt. \nGgf. an Kollegen mit FLIMAS-Desktop wenden.";
        }
        return new JsonResponse($responseData);
    }

    protected function dispatchSave(BaseManager $repository, Request $request)
    {
        $entity = $repository->create(\json_decode($request->getContent(), true));
        $entity->setUserId($repository->getUserId());
        $repository->save($entity);
        return new JsonResponse($entity->toArray());
    }

    /**
     * @param Element $element
     * @param Request $request
     * @return ExportResponse
     */
    protected function exportAction(Element $element, Request $request)
    {
        $ids = $request->request->get('ids', array());

        $query = $this->queryManager->getById($request->request->get('queryId'));
        $ftConfig = $this->configFilter->getFeatureTypeConfigForSchema($element, $query->getSchemaId());
        $featureType = $this->repositoryRegistry->dataStoreFactory($ftConfig);
        $exportConfig = $ftConfig['export'];
        $connection = $featureType->getConnection();
        $fileName = $query->getName() . " " . date('Y:m:d H:i:s');

        $sql = 'SELECT * FROM ' . $connection->quoteIdentifier($featureType->getTableName());
        if ($ids) {
            $sql .= ' WHERE ' . $connection->quoteIdentifier($featureType->getUniqueId()) . ' IN (' . implode(', ', array_map('intval', $ids)) . ')';
        } else {
            $sql .= ' WHERE ' . $this->formatFetchConditionsSql($featureType->getConnection(), $query->getConditions());
        }

        $rows = array();
        foreach ($connection->fetchAll($sql) as $dbRow) {
            if (!empty($exportConfig['fields'])) {
                $exportRow = array();
                foreach ($exportConfig['fields'] as $cellTitle => $cellExpression) {
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

    protected static function formatFetchConditionsSql(Connection $connection, $conditions)
    {
        $sqlConditions = array();
        foreach ($conditions ?: array() as $condition) {
            $sqlConditions[] = implode(' ', array(
                $connection->quoteIdentifier($condition['fieldName']),
                ' ',
                $condition['operator'],
                ' ',
                $connection->quote($condition['value']),
            ));
        }
        return $sqlConditions ?
            implode(' AND ', $sqlConditions)
            : '1=1'
        ;

    }
}
