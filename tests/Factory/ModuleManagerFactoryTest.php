<?php

use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\ServiceManager;
use Laminas\EventManager\EventManager;

use Obullo\Application;
use Obullo\Http\ServerRequest;
use Obullo\Container\ServiceManagerConfig;

use Laminas\Config\Config;
use Laminas\ModuleManager\ModuleManager;

class ModuleManagerFactoryTest extends TestCase
{
    public function setUp() : void
    {
        $appConfig = require __DIR__.'/../config/application.config.php';

        $smConfig = isset($appConfig['service_manager']) ? $appConfig['service_manager'] : [];
        $smConfig = new Obullo\Container\ServiceManagerConfig($smConfig);
        $this->container = new ServiceManager;
        $smConfig->configureServiceManager($this->container);
        $this->container->setService('appConfig', $appConfig);

        $this->container->setFactory(Config::class, 'Obullo\Factory\ConfigFactory');
        $this->container->setFactory('ModuleManager', 'Obullo\Factory\ModuleManagerFactory');
        $this->container->setFactory(ModuleManager::class, 'Obullo\Factory\ModuleManagerFactory');
        $this->container->setFactory(Application::class, 'Obullo\Factory\ApplicationFactory');
        $this->container->setFactory('EventManager', 'Obullo\Factory\EventManagerFactory');
    }

    public function testFactory()
    {
        $instance = $this->container->get('ModuleManager');
        
        $this->assertInstanceOf('Laminas\ModuleManager\ModuleManager', $instance);
    }
}
