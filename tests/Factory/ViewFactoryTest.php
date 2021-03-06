<?php

use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\View;

class ViewFactoryTest extends TestCase
{
    public function setUp() : void
    {
        $appConfig = require __DIR__ . '/../config/application.config.php';
        $smConfig = isset($appConfig['service_manager']) ? $appConfig['service_manager'] : [];
        $smConfig = new Obullo\Container\ServiceManagerConfig($smConfig);

        // setup service manager
        //
        $this->container = new ServiceManager;
        $smConfig->configureServiceManager($this->container);
        $this->container->setService('appConfig', $appConfig);

        $this->container->get('ModuleManager')->loadModules();
    }

    public function testFactory()
    {
        $instance = $this->container->get(View::class);
        
        $this->assertInstanceOf('Laminas\View\View', $instance);
    }
}
