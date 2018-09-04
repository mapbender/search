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

    public function testSave()
    {
        $query = $this->getMockupQuery();
        $hkv   = $this->queryManager->save($query);

        $saveFailedMessage = "Querymanager could not save the query: " . json_encode($query->toArray());
        $idKey             = "id";

        self::assertNotNull($hkv, $saveFailedMessage);
        self::assertObjectHasAttribute($idKey, $hkv, $saveFailedMessage);
        self::assertNotEquals(null, $hkv->getId(), "\$hkv has no id field because it was not saved properly!: " . json_encode($hkv));
    }

    public function testGetById()
    {

        $query = $this->getMockupQuery();
        $this->queryManager->save($query);
        $result = $this->queryManager->getById($query->getId());

        $getByIdFailedMessage = "ID: " . $query->getId() . " QueryManager could not resolve the query:" . json_encode($query->toArray());
        $notEqualErrorMessage = "The saved query is not the same as the one you've got from the database. There must be an error with the saving!";

        self::assertNotNull($result, $getByIdFailedMessage);
        self::assertEquals($query, $result, $notEqualErrorMessage, 0.0, 50);
    }


    public function testListQueries()
    {
        $query = $this->getMockupQuery();
        $this->queryManager->save($query);
        $queryList = $this->queryManager->listQueries();
        $count     = $queryList != null ? count($queryList) : 0;
        self::assertGreaterThan(0, $count);
        $notContainedInListErrorMessage = "The saved Query is not contained by the query list!";
        self::assertEquals($query, $queryList[$query->getId()], $notContainedInListErrorMessage);
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
                           "styleMap"   => $styleMap

        );

        return $this->queryManager->create($queryArgs);

    }


}