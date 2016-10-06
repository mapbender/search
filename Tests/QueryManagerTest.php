<?php

namespace Mapbender\DataSourceBundle\Tests;

use Mapbender\SearchBundle\Entity\StyleMap;
use Mapbender\SearchBundle\Component\QueryManager;
use Mapbender\SearchBundle\Entity\QueryCondition;

/**
 * Class QueryManagerTest
 *
 * @package Mapbender\DataSourceBundle\Tests
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class QueryManagerTest extends SymfonyTest2
{


    /** @var QueryManager */
    protected $queryManager;


    protected function setUp()
    {
        $this->queryManager = $this->getQueryManager();

    }

    protected function tearDown()
    {
        $dropDatabaseFailedMessage = "It was not possible to drop the Database";
        self::assertTrue($this->queryManager->dropDatabase(), $dropDatabaseFailedMessage);

    }

    public function testSave()
    {

        $query = $this->getMockupQuery();
        $hkv   = $this->queryManager->save($query);

        $saveFailedMessage = "Querymanager could not save the query: " . json_encode($query->toArray());
        $idKey             = "id";
        self::assertObjectHasAttribute($idKey, $hkv, $saveFailedMessage);
        self::assertNotEquals(null, $hkv->getId(), json_encode($hkv));
    }

    public function testGetById()
    {

        $query = $this->getMockupQuery();
        $this->queryManager->save($query);
        $result = $this->queryManager->getById($query->getId());

        $getByIdFailedMessage = "ID: " . $query->getId() . " QueryManager could not resolve the query:" . json_encode($query->toArray());
        self::assertNotNull($result, $getByIdFailedMessage);

    }


    public function testListQueries()
    {
        $query = $this->getMockupQuery();
        $this->queryManager->save($query);
        $queryList = $this->queryManager->listQueries();
        $count     = $queryList != null ? count($queryList) : 0;
        self::assertGreaterThan(0, $count);
    }

    public function testRemove()
    {
        $query                 = $this->getMockupQuery();
        $hkv                   = $this->queryManager->save($query);
        $removingFailedMessage = "The Querymanager could not resolve the query : " . json_encode($query->toArray());

        $this->queryManager->remove($query->getId());
        self::assertNull($this->queryManager->getById($query->getId()), $removingFailedMessage);
    }


    /**
     * Helpmethods
     */

    /**
     * @return QueryManager
     */
    public function getQueryManager()
    {
        $container = self::$container;
        return new QueryManager($container);
    }

    /**
     * @return \Mapbender\SearchBundle\Entity\Query
     */
    public function getMockupQuery()
    {
        $queryConditionArgs = array("fieldName" => "name",
                                    "operator"  => "LIKE",
                                    "value"     => "Matthias",
                                    "sql"       => ""
        );

        $queryCondition  = new QueryCondition($queryConditionArgs);
        $queryConditions = array($queryCondition);

        $styleMap  = new StyleMap();
        $queryArgs = array("name"       => "Test",
                           "conditions" => $queryConditions,
                           "styleMap"   => $styleMap,
                           "userId"     => 0
        );

        return $this->queryManager->create($queryArgs);

    }


}