<?php

namespace Mapbender\SearchBundle\Tests;

use Mapbender\SearchBundle\Component\ManagerInterface;

use Mapbender\SearchBundle\Controller\MapbenderController;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class ManagerTest
 *
 * @package Mapbender\DataSourceBundle\Tests
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class ManagerTest extends WebTestCase
{

    /** @var ManagerInterface|MapbenderController */
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

    /**
     * @param Client $client
     * @param string $username
     * @param string $password
     */
    protected function login($client, $username = null, $password = null)
    {

        $session = $client->getContainer()->get('session');

        // the firewall context (defaults to the firewall name)
        $firewall = 'secured_area';

        $token = new UsernamePasswordToken('root', "root", $firewall, array('ROLE_ADMIN'));
        $session->set('_security_'.$firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);





    }


}