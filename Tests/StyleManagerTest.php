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

        $styleMap = $this->getMockupStyleMap();
        $hkv      = $this->styleManager->save($styleMap);

        $saveFailedMessage = "StyleManager could not save the stylemap: " . json_encode($styleMap->toArray());
        $idKey             = "id";

        self::assertNotNull($hkv, $saveFailedMessage);
        self::assertObjectHasAttribute($idKey, $hkv, $saveFailedMessage);
        self::assertNotEquals(null, $hkv->getId(), "\$hkv has no id field because it was not saved properly!: " . json_encode($hkv));
    }

    public function testGetById()
    {

        $styleMap = $this->getMockupStyleMap();
        $this->styleManager->save($styleMap);
        $result = $this->styleManager->getById($styleMap->getId());
        $getByIdFailedMessage = "ID: " . $styleMap->getId() . " StyleManager could not resolve the stylemap:" . json_encode($styleMap->toArray());
        self::assertNotNull($result, $getByIdFailedMessage);


    }


    public function testListQueries()
    {
        $styleMap = $this->getMockupStyleMap();
        $this->styleManager->save($styleMap);
        $styleMaps = $this->styleManager->listStyleMaps();
        $count     = $styleMaps != null ? count($styleMaps) : 0;
        self::assertGreaterThan(0, $count);
        $notContainedInListErrorMessage = "The saved StyleMap is not contained by the stylemap list!";
        self::assertEquals($styleMap, $styleMaps[$styleMap->getId()], $notContainedInListErrorMessage);

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

        $stylesData = array("name"            => "DefaultStyle",
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

        return $this->styleManager->createStyleMap(array("styles"=>$stylesData));

    }


}