<?php

namespace Mapbender\DataSourceBundle\Tests;

use Mapbender\SearchBundle\Component\StyleManager;
use Mapbender\SearchBundle\Entity\StyleMap;

/**
 * Class StyleManagerTest
 *
 * @package Mapbender\DataSourceBundle\Tests
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class StyleManagerTest extends SymfonyTest2
{


    /** @var StyleManager Style Manager */
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

        $styleData = array("name"            => "DefaultStyle",
                           "borderSize"      => 5,
                           "borderColor"     => "0c0c0c",
                           "borderAlpha"     => 255,
                           "backgroundSize"  => 41,
                           "backgroundAlpha" => 255,
                           "backgroundColor" => "fc0c0c",
                           "graphicWidth"    => "100px",
                           "graphicHeight"   => "100px",
                           "graphicOpacity"  => "50%",
                           "graphicXOffset"  => "40px",
                           "graphicYOffset"  => "20px",
                           "graphicName"     => "DefaultGraphic",
                           "externalGraphic" => "$(default)",
                           "fillOpacity"     => "50%",
                           "fillColor"       => "#411232",
                           "strokeColor"     => "#dcc23a",
                           "strokeOpacity"   => "30%",
                           "strokeWidth"     => "3px"
        );

        return $this->styleManager->createStyleMap($styleData);

    }


}