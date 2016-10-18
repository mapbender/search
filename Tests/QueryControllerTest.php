<?php

namespace Mapbender\SearchBundle\Tests;

use Eslider\Driver\HKVStorage;
use Mapbender\SearchBundle\Entity\Query;
use Mapbender\SearchBundle\Entity\QueryCondition;
use Mapbender\SearchBundle\Entity\StyleMap;

/**
 * Class QueryControllerTest
 *
 * @package Mapbender\SearchBundle\Tests
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class QueryControllerTest extends ControllerTest
{
    /**@var array */
    private $queryData;


    /**@var string */
    private $saveRoute;

    /**@var string */
    private $listRoute;

    /**@var string */
    private $authErrorMessage;

    /**@var string */
    private $saveErrorMessage;

    protected function setUp()
    {

        $queryConditionArgs = array("fieldName" => "name",
                                    "operator"  => "LIKE",
                                    "value"     => "Matthias",
                                    "sql"       => ""
        );
        $queryCondition     = new QueryCondition($queryConditionArgs);
        $queryConditions    = array($queryCondition);

        $styleMap  = new StyleMap();
        $queryArgs = array("name"       => "Test",
                           "conditions" => $queryConditions,
                           "styleMap"   => $styleMap

        );

        $this->queryData = $queryArgs;

        $this->routeBase        = '/query/';
        $this->serviceName      = "mapbender.query.manager";
        $this->updateRoute      = $this->routeBase . 'update';
        $this->authErrorMessage = "Failed to get/remove Query because of a lack of permissions!";
        $this->saveErrorMessage = "Failed to get/remove Query for given id. It was probably not correctly saved. Check the save/update test case.";
        parent::setUp();

    }


    /**
     * Get the controller get route based on the id
     *
     * @param $id
     * @return string
     */
    private function getGetRoute($id)
    {
        return $this->routeBase . $id . "/get/";
    }


    public function testGet()
    {

        $mockQuery = $this->saveMockQuery();
        $hkv       = HKVStorage::decodeValue($mockQuery);
        $id        = $hkv->getValue()->getId();
        $client    = static::createClient();
        $this->login($client, "root", "root");
        $client->request('get', $this->getGetRoute($id));
        $response = $client->getResponse();
        $query    = HKVStorage::decodeValue($response->getContent());

        $this->assertEquals($hkv->getValue(), $query, "Saved and fetched query not equal.");
        $this->assertNotNull($query, "Response may not be null.");
    }

    public function testList()
    {

        $mockQueries        = array();
        $decodedMockQueries = array();

        for ($i = 0; $i < 10; $i++) {
            $mockQuery            = $this->saveMockQuery();
            $mockQueries[]        = $mockQuery;
            $decodedMockQueries[] = HKVStorage::decodeValue($mockQuery);
        }

        $client = static::createClient();
        $this->login($client, "root", "root");
        $client->request('get', $this->listRoute);
        $response = $client->getResponse();
        $query    = HKVStorage::decodeValue($response->getContent());

        self::assertNotNull($query, "List is null despite being filled by at least 10 style maps.");
        foreach ($decodedMockQueries as $k => $v) {
            self::assertNotNull($query[ $v->getValue()->getId() ], "At least one query was not saved.");
        }
    }


    /**
     * @return array
     */
    private function saveMockQuery()
    {
        $client = static::createClient();
        $this->login($client, "root", "root");
        $query      = $this->getMockQuery();
        $queryArray = $query->toArray();
        $queryJson  = json_encode($queryArray);
        $client->request('POST', $this->saveRoute, $queryArray, array(), array('CONTENT_TYPE' => 'application/json'), $queryJson);
        return $client->getResponse()->getContent();

    }

    /**
     * @return Query
     */
    private function getMockQuery()
    {
        return $this->manager->create($this->queryData);
    }


}