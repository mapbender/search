<?php

namespace Mapbender\SearchBundle\Tests;

use Mapbender\SearchBundle\Component\ManagerInterface;

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
    protected $_kernel;

    /**@var string */
    protected $routeBase;

    protected function setUp()
    {
        if (!isset($this->serviceName)) {
            throw new Exception("Service name has to be set");
        }
        $this->_kernel = $this->createKernel();
        $this->_kernel->boot();
        try {
            $this->manager = $this->_kernel->getContainer()->get($this->serviceName);
        } catch (ServiceNotFoundException $notFoundException) {
            throw new Exception("The service " . $this->serviceName . " has to be registered.");
        }
    }

}