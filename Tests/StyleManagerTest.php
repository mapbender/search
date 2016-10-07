<?php

namespace Mapbender\DataSourceBundle\Tests;

use Mapbender\SearchBundle\Component\StyleManager;
use Mapbender\SearchBundle\Entity\StyleMap;
use Mapbender\SearchBundle\Entity\QueryCondition;

/**
 * Class StyleManagerTest
 *
 * @package Mapbender\DataSourceBundle\Tests
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class StyleManagerTest extends SymfonyTest2
{


    /** @var StyleManager */
    protected $styleManager;


    protected function setUp()
    {
        $this->styleManager = $this->getStyleManager();

    }

    protected function tearDown()
    {
        $dropDatabaseFailedMessage = "It was not possible to drop the Database";
        self::assertTrue($this->styleManager->dropDatabase(), $dropDatabaseFailedMessage);

    }

    public function testSave()
    {

        $query = $this->getMockupStyleMap();
        $hkv   = $this->styleManager->save($query);

        $saveFailedMessage = "StyleManager could not save the query: " . json_encode($query->toArray());
        $idKey             = "id";
        self::assertObjectHasAttribute($idKey, $hkv, $saveFailedMessage);
        self::assertNotEquals(null, $hkv->getId(), json_encode($hkv));
    }

    public function testGetById()
    {

        $query = $this->getMockupStyleMap();
        $this->styleManager->save($query);
        $result = $this->styleManager->getById($query->getId());

        $getByIdFailedMessage = "ID: " . $query->getId() . " StyleManager could not resolve the query:" . json_encode($query->toArray());
        self::assertNotNull($result, $getByIdFailedMessage);

    }


    public function testListQueries()
    {
        $query = $this->getMockupStyleMap();
        $this->styleManager->save($query);
        $queryList = $this->styleManager->listStyleMaps();
        $count     = $queryList != null ? count($queryList) : 0;
        self::assertGreaterThan(0, $count);
    }

    public function testRemove()
    {
        $query = $this->getMockupStyleMap();
        $this->styleManager->save($query);
        $removingFailedMessage = "The StyleManager could not resolve the query : " . json_encode($query->toArray());

        $this->styleManager->remove($query->getId());
        self::assertNull($this->styleManager->getById($query->getId()), $removingFailedMessage);
    }


    /**
     * Help methods
     */

    /**
     * @return StyleManager
     */
    public function getStyleManager()
    {
        $container = self::$container;
        return new StyleManager($container);
    }

    /**
     * @return StyleMap
     */
    public function getMockupStyleMap()
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

        return $this->styleManager->create($queryArgs);

    }


}