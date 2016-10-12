<?php

namespace Mapbender\DataSourceBundle\Tests;

use Mapbender\SearchBundle\Component\ManagerInterface;
use Mapbender\SearchBundle\Component\StyleManager;
use Mapbender\SearchBundle\Entity\StyleMap;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class ControllerTest
 *
 * @package Mapbender\DataSourceBundle\Tests
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class ControllerTest extends WebTestCase
{

    /** @var ManagerInterface */
    protected $manager;
    /** @var string */
    protected $serviceName;

    /** @var KernelInterface */
    protected $kernel;

    /**@var string */
    protected $routeBase;

    protected function setUp()
    {
        if (isset($this->serviceName)) {
            throw new Exception("StyleName has to be set");
        }
        $this->kernel = $this->createKernel();
        $this->kernel->boot();
        try {
            $this->manager = $this->kernel->getContainer()->get($this->serviceName);
        } catch (ServiceNotFoundException $notFoundException) {
            throw new Exception("The service " . $this->serviceName . " has to be registered.");
        }
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
        list($crawler, $style) = $this->saveMockStyleMap();

        $updateFailedMessage = "";
        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("' . $style->getName() . '")')->count(), $updateFailedMessage
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
     * @param $action
     */
    private function getOrRemoveMockStyleMapById($action)
    {
        $crawlerAndStyle = $this->saveMockStyleMap();
        /** @var StyleMap $style */
        $style = $crawlerAndStyle["style"];

        $id      = $style->getId();
        $client  = static::createClient();
        $crawler = null;
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
        return $this->manager->create($this->styleData);
    }
}