<?php

namespace Mapbender\SearchBundle\Tests;

use Mapbender\SearchBundle\Component\StyleManager;
use Mapbender\SearchBundle\Entity\StyleMap;

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
        $this->saveRoute        = $this->routeBase . 'update';
        $this->authErrorMessage = "Failed to get/remove StyleMap because of a lack of permissions!";
        $this->saveErrorMessage = "Failed to get/remove StyleMap for given id. It was probably not correctly saved. Check the save/update test case.";
        parent::setUp();

    }

    /**
     *  Get the controller remove route based on the id
     *
     * @param $id
     * @return string
     */
    private function getRemoveRoute($id)
    {
        return $this->routeBase . $id . "/remove";
    }

    /**
     * Get the controller get route based on the id
     *
     * @param $id
     * @return string
     */
    private function getGetRoute($id)
    {
        return $this->routeBase . $id;
    }

    public function testUpdate()
    {
        /**@var StyleMap $styleMap */
        list($crawler, $styleMap) = $this->saveMockStyleMap();
        $updateFailedMessage = "Failed to update/create StyleMap: " . json_encode($styleMap->toArray());
        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("' . $styleMap->getName() . '")')->count(), $updateFailedMessage
        );

    }


    public function testGet()
    {

        $this->getOrRemoveMockStyleMapById("get");

    }

    public function testRemove()
    {
        $this->getOrRemoveMockStyleMapById("remove");
    }


    /**
     * @param string $action
     */
    private function getOrRemoveMockStyleMapById($action)
    {
        /**@var StyleMap $styleMap */

        list($crawler, $styleMap) = $this->saveMockStyleMap();
        $id     = $styleMap->getId();
        $client = static::createClient();

        switch ($action) {
            case "get":
                $crawler = $client->request('get', $this->getGetRoute($id));
                break;
            case "remove":
                $crawler = $client->request('get', $this->getRemoveRoute($id));
                break;
        }

        $this->assertLessThan(
            1,
            $crawler->filter('html:contains("' . "not authorized" . '")')->count(), $this->authErrorMessage
        );

        $this->assertLessThan(
            1,
            $crawler->filter('html:contains("' . "not alter/find stylemap" . '")')->count(), $this->saveErrorMessage
        );
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

        $crawler  = $client->request('POST', $this->saveRoute, $styleMapArray, array(), array('CONTENT_TYPE' => 'application/json'), $styleMapJson);
        $response = $client->getResponse();
        var_dump($response->getContent());

        $style = $styleMap->getStyles();
        return array($crawler, $style);

    }

    /**
     * @return StyleMap
     */
    private function getMockStyleMap()
    {
        return $this->manager->create($this->styleData);
    }
}