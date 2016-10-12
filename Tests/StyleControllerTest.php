<?php

namespace Mapbender\DataSourceBundle\Tests;

use Mapbender\SearchBundle\Component\StyleManager;
use Mapbender\SearchBundle\Entity\StyleMap;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class StyleControllerTest
 *
 * @package Mapbender\DataSourceBundle\Tests
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class StyleControllerTest extends WebTestCase
{

    /** @var StyleManager */
    private $styleManager;

    /** @var KernelInterface */
    private $styleControllerKernel;

    /**@var array */
    private $styleData;

    /**@var string */
    private $routeBase;

    /**@var string */
    private $updateRoute;
    /**@var string */

    private $authErrorMessage;

    /**@var string */
    private $saveErrorMessage;

    protected function setUp()
    {

        $this->styleControllerKernel = $this->createKernel();
        $this->styleControllerKernel->boot();

        $this->styleManager = $this->styleControllerKernel->getContainer()->get("mapbender.style.manager");
        $this->styleData    = array("name"            => "DefaultStyle",
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
        $this->updateRoute      = $this->routeBase . 'update';
        $this->authErrorMessage = "Failed to get/remove StyleMap because of a lack of permissions!";
        $this->saveErrorMessage = "Failed to get/remove StyleMap for given id. It was probably not correctly saved. Check the save/update test case.";

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
        list($crawler, $styleMap) = $this->saveMockStyleMap();
        $updateFailedMessage = "";
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
        /**@var StyleMap $styleMap*/

        list($crawler,$styleMap) = $this->saveMockStyleMap();
        $id      = $styleMap->getId();
        $client  = static::createClient();
        
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
        $client   = static::createClient();
        $styleMap = $this->getMockStyleMap();
        $crawler  = $client->request('POST', $this->updateRoute, array(), array(), array(), json_encode($styleMap->toArray()));
        $style    = $styleMap->pop();
        return array($crawler, $style);

    }
    /**
     * @return StyleMap
     */
    private function getMockStyleMap()
    {
        return $this->styleManager->create($this->styleData);
    }
}