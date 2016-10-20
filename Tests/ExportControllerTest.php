<?php

namespace Mapbender\SearchBundle\Tests;

use Mapbender\DataSourceBundle\Component\FeatureTypeService;
use Mapbender\SearchBundle\Component\QueryManager;
use Mapbender\SearchBundle\Entity\ExportRequest;
use Mapbender\SearchBundle\Entity\QueryCondition;
use Mapbender\SearchBundle\Entity\StyleMap;

/**
 * Class ExportControllerTest
 *
 * @package Mapbender\SearchBundle\Tests
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class ExportControllerTest extends ManagerTest
{


    /**@var FeatureTypeService */
    protected $featureService;

    /**@var QueryManager */
    protected $queryManager;

    protected function setUp()
    {
        $this->serviceName = "mapbender.export.controller";
        $this->routeBase   = "/export/";
        $this->getRoute    = $this->routeBase . "export";
        parent::setUp();
        $this->queryManager = $this->_kernel->getContainer()->get("mapbender.query.manager");

    }


    /**
     * @return \Mapbender\SearchBundle\Entity\Query
     */
    public function getMockupQuery($featureType)
    {
        $queryConditionArgs = array("fieldName" => "name",
                                    "operator"  => "LIKE",
                                    "value"     => "Matthias",
                                    "sql"       => ""
        );

        $queryCondition  = new QueryCondition($queryConditionArgs);
        $queryConditions = array($queryCondition);

        $styleMap  = new StyleMap();
        $queryArgs = array("name"        => "Test",
                           "conditions"  => $queryConditions,
                           "styleMap"    => $styleMap,
                           "featureType" => $featureType

        );

        return $this->queryManager->create($queryArgs);

    }


    public function testExport()
    {

        $client        = static::createClient();
        $exportRequest = $this->saveQueriesWithMockFeatureTypes();
        $exportResponse = $this->manager->export($exportRequest);


    }


    /**
     * @return ExportRequest
     */
    protected function saveQueriesWithMockFeatureTypes()
    {
        $featureTypeDeclarations = array("type-1", "type-2");
        foreach ($featureTypeDeclarations as $key => $declaration) {
            $this->queryManager->save($this->getMockupQuery($declaration));
        }

        $exportRequest = new ExportRequest(array());
        $exportRequest->setIds($featureTypeDeclarations);
        return $exportRequest;
    }


}