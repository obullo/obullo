<?php

use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\ServiceManager;

class ViewHelperManagerFactoryTest extends TestCase
{
    public function setUp() : void
    {
        $appConfig = require __DIR__.'/../config/application.config.php';

        $smConfig = isset($appConfig['service_manager']) ? $appConfig['service_manager'] : [];
        $smConfig = new Obullo\Container\ServiceManagerConfig($smConfig);
        $this->container = new ServiceManager;
        $smConfig->configureServiceManager($this->container);
        $this->container->setFactory('ViewPhpRenderer', 'Obullo\Factory\ViewPhpRendererFactory');
        $this->container->setFactory('ViewHelperManager', 'Obullo\Factory\ViewHelperManagerFactory');
    }

    public function testFactory()
    {
        $instance = $this->container->get('ViewHelperManager');
        
        $this->assertInstanceOf('Laminas\View\HelperPluginManager', $instance);
    }
}
