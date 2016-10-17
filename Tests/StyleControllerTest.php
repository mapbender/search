<?php

namespace Mapbender\SearchBundle\Tests;

use Eslider\Driver\HKVStorage;
use Eslider\Entity\HKV;
use Mapbender\SearchBundle\Component\StyleManager;
use Mapbender\SearchBundle\Entity\StyleMap;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class StyleControllerTest
 *
 * @package Mapbender\DataSourceBundle\Tests
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class StyleControllerTest extends ControllerTest
{
    /**@var array */
    private $styleData;

    /**@var string */
    private $saveRoute;

    /**@var string */
    private $authErrorMessage;

    /**@var string */
    private $saveErrorMessage;

    protected function setUp()
    {
        $this->styleData        = array("name"            => "DefaultStyle",
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
        $this->routeBase        = '/style/';
        $this->serviceName      = "mapbender.style.manager";
        $this->saveRoute        = $this->routeBase . 'save';
        $this->authErrorMessage = "Failed to get/remove StyleMap because of a lack of permissions!";
        $this->saveErrorMessage = "Failed to get/remove StyleMap for given id. It was probably not correctly saved. Check the save/update test case.";
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
        return $this->routeBase . "get/" . $id;
    }


    public function testGet()
    {

        $mockStyleMap = $this->saveMockStyleMap();
        $hkv          = HKVStorage::decodeValue($mockStyleMap);
        $id           = $hkv->getValue()->getId();
        $client       = static::createClient();
        $client->request('get', $this->getGetRoute($id));
        $response = $client->getResponse();
        $styleMap = HKVStorage::decodeValue($response->getContent());

        $this->assertEquals($hkv->getValue(), $styleMap, "Saved and fetched stylemap not equal.");
        $this->assertNotNull($styleMap, "Response may not be null.");
    }


    /**
     * @return array
     */
    private function saveMockStyleMap()
    {
        $client        = static::createClient();
        $styleMap      = $this->getMockStyleMap();
        $styleMapArray = $styleMap->toArray();
        $styleMapJson  = json_encode($styleMapArray);
        $client->request('POST', $this->saveRoute, $styleMapArray, array(), array('CONTENT_TYPE' => 'application/json'), $styleMapJson);
        return $client->getResponse()->getContent();

    }


    /**
     * @return StyleMap
     */
    private function getMockStyleMap()
    {
        return $this->manager->create($this->styleData);
    }
}