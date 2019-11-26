<?php

use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\ServiceManager;
use Obullo\Http\ServerRequest;

class RequestFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = new ServiceManager;
        $this->container->setFactory(ServerRequest::class, 'Obullo\Factory\RequestFactory');
    }

    public function testFactory()
    {
        $instance = $this->container->get(ServerRequest::class);
        
        $this->assertInstanceOf('Obullo\Http\ServerRequest', $instance);
        $this->assertInstanceOf('Zend\Diactoros\ServerRequest', $instance);
    }
}