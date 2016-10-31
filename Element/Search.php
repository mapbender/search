<?php

namespace Mapbender\SearchBundle\Element;

use Doctrine\DBAL\DBALException;
use Eslider\Driver\HKVStorage;
use FOM\CoreBundle\Component\ExportResponse;
use Mapbender\DataSourceBundle\Component\FeatureType;
use Mapbender\DataSourceBundle\Element\BaseElement;
use Mapbender\DataSourceBundle\Entity\Feature;
use Mapbender\DigitizerBundle\Component\Uploader;
use Mapbender\SearchBundle\Entity\ExportRequest;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zumba\Util\JsonSerializer;

/**
 * Class Search
 *
 * @package Mapbender\SearchBundle\Element
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class Search extends BaseElement
{
    /** @var string Element title */
    protected static $title       = 'Search';

    /** @var string Element description */
    protected static $description = 'Object search element';

    /** @var FeatureType Current feature type */
    protected $featureType;

    /** @inheritdoc */
    static public function listAssets()
    {
        return array(
            'js'    =>
                array(
                    '../../vendor/blueimp/jquery-file-upload/js/jquery.fileupload.js',
                    '../../vendor/blueimp/jquery-file-upload/js/jquery.iframe-transport.js',
                    '/components/jquery-context-menu/jquery-context-menu-built.js',
                    '/components/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js',
                    'feature-style-editor.js',
                    'style-map-manager.js',
                    'query-manager.js',
                    'mapbender.element.search.js'
                ),
            'css'   => array('sass/element/search.scss'),
            'trans' => array('MapbenderSearchBundle:Element:search.json.twig'));
    }

    /** @inheritdoc */
    public static function getDefaultConfiguration()
    {
        return array(
            'target' => null
        );
    }

    /**
     * Prepare form items for each scheme definition
     * Optional: get featureType by name from global context.
     *
     * @inheritdoc
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    public function getConfiguration()
    {
        $configuration            = parent::getConfiguration();
        $configuration['debug']   = isset($configuration['debug']) ? $configuration['debug'] : false;
        $configuration['fileUri'] = $this->container->getParameter('mapbender.uploads_dir') . "/" . FeatureType::UPLOAD_DIR_NAME;

        if ($configuration['schemes'] && is_array($configuration['schemes'])) {
            foreach ($configuration['schemes'] as $key => &$scheme) {
                if (is_string($scheme['featureType'])) {
                    $featureTypes          = $this->container->getParameter('featureTypes');
                    $scheme['featureType'] = $featureTypes[ $scheme['featureType'] ];
                }
                if (isset($scheme['formItems'])) {
                    $scheme['formItems'] = $this->prepareItems($scheme['formItems']);
                }
            }
        }
        return $configuration;
    }

    /**
     * Prepare request feautre data by the form definition
     *
     * @param $feature
     * @param $formItems
     * @return array
     */
    protected function prepareQueriedFeatureData($feature, $formItems)
    {
        foreach ($formItems as $key => $formItem) {
            if (isset($formItem['children'])) {
                $feature = array_merge($feature, $this->prepareQueriedFeatureData($feature, $formItem['children']));
            } elseif (isset($formItem['type']) && isset($formItem['name'])) {
                switch ($formItem['type']) {
                    case 'select':
                        if (isset($formItem['multiple'])) {
                            $separator = isset($formItem['separator']) ? $formItem['separator'] : ',';
                            if (is_array($feature["properties"][ $formItem['name'] ])) {
                                $feature["properties"][ $formItem['name'] ] = implode($separator, $feature["properties"][ $formItem['name'] ]);
                            }
                        }
                        break;
                }
            }
        }
        return $feature;
    }

    /**
     * Remove given fields
     *
     * @param $data
     * @return mixed
     */
    protected function filterFields($data, $fields)
    {
        foreach ($fields as $deniedFieldName) {
            if (isset($data[ $deniedFieldName ])) {
                unset($data[ $deniedFieldName ]);
            }
        }
        return $data;
    }

    /**
     * @return int
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    protected function getUserId()
    {
        return $this->container->get('security.context')->getUser()->getId();
    }

    /**
     * @return array|mixed
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    protected function getRequestData()
    {
        $content = $this->container->get('request')->getContent();
        $request = array_merge($_POST, $_GET);

        if (!empty($content)) {
            $request = array_merge($request, json_decode($content, true));
        }

        return $this->decodeRequest($request);
    }

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    public function httpAction($action)
    {
        /** @var $requestService Request */
        $configuration  = $this->getConfiguration();
        $requestService = $this->container->get('request');
        $request        = $this->getRequestData();
        $schemas        = $configuration['schemes'];
        $featureType    = null;
        $debugMode      = $configuration['debug'] || $this->container->get('kernel')->getEnvironment() == 'dev';
        $schemaName     = isset($request['schema']) ? $request['schema'] : $requestService->get('schema');
        if (!empty($schemaName)) {
            //throw new Exception('For initialization there is no name of the declared scheme');
            $schema = $schemas[ $schemaName ];
            if (is_array($schema['featureType'])) {
                $featureType = new FeatureType($this->container, $schema['featureType']);
                $this->setFeatureType($featureType);
            } else {
                throw new Exception('FeatureType settings not correct');
            }
        }

        $results = array();

        switch ($action) {
            case 'save':
                // save once
                if (isset($request['feature'])) {
                    $request['features'] = array($request['feature']);
                }

                $connection = $featureType->getDriver()->getConnection();

                try {
                    // save collection
                    if (isset($request['features']) && is_array($request['features'])) {
                        foreach ($request['features'] as $feature) {
                            /**
                             * @var $feature Feature
                             */
                            $featureData = $this->prepareQueriedFeatureData($feature, $schema['formItems']);

                            foreach ($featureType->getFileInfo() as $fileConfig) {
                                if (!isset($fileConfig['field']) || !isset($featureData["properties"][$fileConfig['field']])) {
                                    continue;
                                }
                                $url                                             = $featureType->getFileUrl($fileConfig['field']);
                                $requestUrl                                      = $featureData["properties"][$fileConfig['field']];
                                $newUrl                                          = str_replace($url . "/", "", $requestUrl);
                                $featureData["properties"][$fileConfig['field']] = $newUrl;
                            }

                            $feature = $featureType->save($featureData);
                            $results = array_merge($featureType->search(array(
                                'srid'  => $feature->getSrid(),
                                'where' => $connection->quoteIdentifier($featureType->getUniqueId()) . '=' . $connection->quote($feature->getId()))));
                        }
                    }
                    $results = $featureType->toFeatureCollection($results);
                } catch (DBALException $e) {
                    $message = $debugMode ? $e->getMessage() : "Feature can't be saved. Maybe something is wrong configured or your database isn't available?\n" .
                        "For more information have a look at the webserver log file. \n Error code: " .$e->getCode();
                    $results = array('errors' => array(
                        array('message' => $message, 'code' => $e->getCode())
                    ));
                }

                break;

            case 'delete':
                $results = $featureType->remove($request['feature']);
                break;

            case 'file-upload':
                $fieldName                  = $requestService->get('field');
                $urlParameters              = array('schema' => $schemaName,
                                                    'fid'    => $requestService->get('fid'),
                                                    'field'  => $fieldName);
                $serverUrl                  = preg_replace('/\\?.+$/', "", $_SERVER["REQUEST_URI"]) . "?" . http_build_query($urlParameters);
                $uploadDir                  = $featureType->getFilePath($fieldName);
                $uploadUrl                  = $featureType->getFileUrl($fieldName) . "/";
                $urlParameters['uploadUrl'] = $uploadUrl;
                $uploadHandler              = new Uploader(array(
                    'upload_dir'                   => $uploadDir . "/",
                    'script_url'                   => $serverUrl,
                    'upload_url'                   => $uploadUrl,
                    'accept_file_types'            => '/\.(gif|jpe?g|png)$/i',
                    'print_response'               => false,
                    'access_control_allow_methods' => array(
                        'OPTIONS',
                        'HEAD',
                        'GET',
                        'POST',
                        'PUT',
                        'PATCH',
                        //'DELETE'
                    ),
                ));
                $results                    = array_merge($uploadHandler->get_response(), $urlParameters);

                break;

            case 'datastore/get':
                // TODO: get request ID and check
                if (!isset($request['id']) || !isset($request['dataItemId'])) {
                    $results = array(
                        array('errors' => array(
                            array('message' => $action . ": id or dataItemId not defined!")
                        ))
                    );
                }

                $id           = $request['id'];
                $dataItemId   = $request['dataItemId'];
                $dataStore    = $this->container->get("data.source")->get($id);
                $dataItem     = $dataStore->get($dataItemId);
                $dataItemData = null;
                if ($dataItem) {
                    $dataItemData = $dataItem->toArray();
                    $results      = $dataItemData;
                }
                break;

            case 'datastore/save':

                $id          = $request['id'];
                $dataItem    = $request['dataItem'];
                $dataStore   = $this->container->get("data.source")->get($id);
                $uniqueIdKey = $dataStore->getDriver()->getUniqueId();
                if (empty($request['dataItem'][ $uniqueIdKey ])) {
                    unset($request['dataItem'][ $uniqueIdKey ]);
                }
                $results = $dataStore->save($dataItem);

                break;
            case 'datastore/remove':
                $id          = $request['id'];
                $dataStore   = $this->container->get("data.source")->get($id);
                $uniqueIdKey = $dataStore->getDriver()->getUniqueId();
                $dataItemId  = $request['dataItem'][ $uniqueIdKey ];
                $dataStore->remove($dataItemId);
                break;

            default:
                $names       = explode('/', $action);
                $names       = array_reverse($names);
                $namesLength = count($names);
                for ($i = 1; $i < $namesLength; $i++) {
                    $names[ $i ][0] = strtoupper($names[ $i ][0]);
                }
                $action     = implode($names);
                $methodName = preg_replace('/[^a-z]+/si', null, $action) . 'Action';
                $result     = $this->{$methodName}($request);

                if (is_array($result)) {
                    $serializer = new JsonSerializer();
                    $result     = new Response($serializer->serialize($result));
                }

                return $result;
        }

        return new JsonResponse($results);
    }

    /**
     * Decode request array variables
     *
     * @param array $request
     * @return mixed
     */
    public function decodeRequest(array $request)
    {
        foreach ($request as $key => $value) {
            if (is_array($value)) {
                $request[ $key ] = $this->decodeRequest($value);
            } elseif (strpos($key, '[')) {
                preg_match('/(.+?)\[(.+?)\]/', $key, $matches);
                list($match, $name, $subKey) = $matches;

                if (!isset($request[ $name ])) {
                    $request[ $name ] = array();
                }

                $request[ $name ][ $subKey ] = $value;
                unset($request[ $key ]);
            }
        }
        return $request;
    }

    /**
     * Set feature type
     *
     * @param $featureType
     */
    private function setFeatureType(FeatureType $featureType)
    {
        $this->featureType = $featureType;
    }

    /**
     * Get feature type
     *
     * @return FeatureType
     */
    public function getFeatureType()
    {
        return $this->featureType;
    }

    /**
     * Export results
     *
     * @param $request
     * @return mixed
     */
    public function exportAction($request)
    {
        $exportRequest = new ExportRequest($request);
        $ids           = $exportRequest->getIds();
        $featureType   = $this->getFeatureType();
        $config        = $featureType->getConfiguration('export');
        $fileName      = isset($config['fileName']) ? $config['fileName'] : "export";

        return new ExportResponse($featureType->exportByIds($ids), $fileName, $request["type"]);
    }

    /**
     * Export results
     *
     * @param $request
     * @return mixed
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function listFeatureTypeAction($request)
    {
        $result             = array();
        $featureTypeManager = $this->container->get('features');

        foreach ($featureTypeManager->getFeatureTypeDeclarations() as $key => $declaration) {
            $title       = isset($declaration['title']) ? $declaration['title'] . " ($key)" : ucfirst($key);
            $featureType = $featureTypeManager->get($key);
            $fieldNames  = $featureType->getFields();
            $operators   = $featureType->getOperators();
            $print       = $featureType->getConfiguration('print');

            $result[ $key ] = array(
                'title'      => $title,
                'fieldNames' => array_combine($fieldNames, $fieldNames),
                'operators'  => array_combine($operators, $operators),
                'print'      => $print
            );
        }

        ksort($result);

        return array(
            'list' => $result
        );
    }

    /**
     * Describe feature type
     *
     * @param $request
     * @return mixed
     */
    public function describeFeatureTypeAction($request)
    {
        $featureType = $this->getFeatureType();
        $fields      = $this->getFeatureType()->getFields();
        $operators   = $featureType->getOperators();

        return new JsonResponse(array(
            'operators'  => array_combine($operators, $operators),
            'print'      => $featureType->getConfiguration('print'),
            'fieldNames' => array_combine($fields, $fields)
        ));
    }

    /**
     * Export results
     *
     * @param $request
     * @return mixed
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function saveQueryAction($request)
    {
        $data         = $this->filterFields($request['query'], array('userId'));
        $queryManager = $this->container->get('mapbender.query.manager');
        $query        = $queryManager->saveArray($data);

        return array(
            'entity' => $query
        );
    }

    /**
     * Save Styles
     *
     * @param $request
     * @return mixed
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function saveStyleAction($request)
    {
        $data         = $this->filterFields($request['style'], array('userId', 'styleMaps', 'pointerEvents'));
        $styleManager = $this->container->get("mapbender.style.manager");
        $style        = $styleManager->createStyle($data);
        $style->setUserId($this->getUserId());
        $style = $styleManager->save($style);
        return array(
            'style' => $style
        );
    }


    /**
     * List styles
     *
     * @param $request
     * @return mixed
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \InvalidArgumentException
     */
    public function listStyleAction($request)
    {
        $styleManager = $this->container->get('mapbender.style.manager');
        return array(
            'list' => $styleManager->listStyles()
        );
    }


    /**
     * Gets a Style Entity via ID
     *
     * @param $request
     * @return mixed
     */
    public function getStyleAction($request)
    {
        $styleManager = $this->container->get("mapbender.style.manager");
        $id           = isset($request["id"]) ? $request["id"] : "UNDEFINED";

        $style = $styleManager->getById($id);
        return new JsonResponse(array(
            'style' => HKVStorage::encodeValue($style)
        ));
    }


    /**
     * Removes a Style Entity via ID
     *
     * @param $request
     * @return mixed
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function removeStyleAction($request)
    {
        $styleManager = $this->container->get('mapbender.style.manager');
        $id           = isset($request["id"]) ? $request["id"] : "UNDEFINED";

        $style = $styleManager->remove($id);
        return new JsonResponse(array(
            'style' => HKVStorage::encodeValue($style)
        ));
    }


    /**
     * Saves a StyleMap Entity
     *
     * @param $request
     * @return array
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function saveStyleMapAction($request)
    {
        $data            = $this->filterFields($request["styleMap"], array('userId'));
        $styleMapManager = $this->container->get("mapbender.stylemap.manager");
        $styleMap        = $styleMapManager->create($data);
        $styleMap->setUserId($this->getUserId());

        $styleMapManager->save($styleMap);

        return array(
            'styleMap' => $styleMap
        );
    }


    /**
     * Lists all StyleMap Entities
     *
     * @param array $request
     * @return mixed
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function listStyleMapAction($request)
    {
        $styleMapManager = $this->container->get('mapbender.stylemap.manager');
        $styleMap        = $styleMapManager->listStyleMaps();
        return array(
            'list' => $styleMap
        );
    }


    /**
     * Gets a StyleMap Entity via ID
     *
     * @param $request
     * @return mixed
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function getStyleMapAction($request)
    {
        $styleMapManager = $this->container->get('mapbender.stylemap.manager');
        $id              = $request['id'];
        $styleMap        = $styleMapManager->getById($id);

        return array(
            'entity' => $styleMap
        );
    }


    /**
     * Removes a Style Entity via ID
     *
     * @param $request
     * @return array
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function removeStyleMapAction($request)
    {
        $id              = $request['id'];
        $styleMapManager = $this->container->get('mapbender.stylemap.manager');
        return array(
            'result' => $styleMapManager->remove($id)
        );
    }


    /**
     * Removes a Style Entity ID from StyleMap Entity
     *
     * @param $request
     * @return array
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function removeStyleFromStyleMapAction($request)
    {
        $styleManager = $this->container->get('mapbender.stylemap.manager');
        $styleMapId   = $request['styleMapId'];
        $styleId      = $request['styleId'];
        return array(
            'result' => $styleManager->removeStyle($styleMapId, $styleId)
        );
    }


    /**
     * Add a Style Entity ID to StyleMap Entity
     *
     * @param $request
     * @return mixed
     */
    public function addStyleToStylemapAction($request)
    {
        $styleMapManager = $this->container->get("mapbender.stylemap.manager");
        $styleMapId      = isset($request["stylemapid"]) ? $request["stylemapid"] : "UNDEFINED";
        $styleId         = isset($request["styleid"]) ? $request["styleid"] : "UNDEFINED";
        $style           = $styleMapManager->addStyle($styleMapId, $styleId);

        return new JsonResponse(array(
            'stylemap' => HKVStorage::encodeValue($style)
        ));
    }

    /**
     * Select features
     *
     * @param $request
     * @return \Mapbender\DataSourceBundle\Entity\Feature[]
     */
    public function selectFeaturesAction($request)
    {
        return $this->getFeatureType()->search(
            array_merge(
                array(
                    'returnType' => 'FeatureCollection',
                    'maxResults' => 2500
                ),
                $request));
    }

    /**
     * List queries
     *
     * @param $request
     * @return \Mapbender\DataSourceBundle\Entity\Feature[]
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function listQueriesAction($request)
    {
        $container    = $this->container;
        $queryManager = $container->get('mapbender.query.manager');

        return array(
            'list' => $queryManager->listQueries()
        );
    }

    /**
     * List queries
     *
     * @param $request
     * @return \Mapbender\DataSourceBundle\Entity\Feature[]
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function checkQueryAction($request)
    {
        $container    = $this->container;
        $queryManager = $container->get('mapbender.query.manager');
        $query        = $queryManager->create($request['query']);
        $queryManager->check($query);

        return array(
            'result' => $queryManager->check($query)
        );
    }

    /**
     * List queries
     *
     * @param $request
     * @return \Mapbender\DataSourceBundle\Entity\Feature[]
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function removeQueryAction($request)
    {
        $container    = $this->container;
        $queryManager = $container->get('mapbender.query.manager');
        $id           = $request['id'];
        return array(
            'result' => $queryManager->removeById($id)
        );
    }
}