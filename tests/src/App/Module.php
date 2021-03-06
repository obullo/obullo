<?php

declare(strict_types=1);

namespace App;

use Obullo\PageEvent;
use Laminas\Diactoros\Response;
use Obullo\Middleware\NotFoundHandler;
use Obullo\Middleware\ErrorHandler;
use Laminas\ModuleManager\ModuleManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

class Module
{
    public function getConfig() : array
    {
        return [
            'service_manager' => [
                'factories' => [
                    'App\Pages\PluginModel' => InvokableFactory::class,
                    'App\Pages\SetLocaleModel' => ReflectionBasedAbstractFactory::class,
                    'App\Pages\TestArgsModel' => InvokableFactory::class,
                    'App\Pages\TestErrorModel' => InvokableFactory::class,
                    'App\Pages\TestHttpModel' => InvokableFactory::class,
                    'App\Pages\TestModel' => InvokableFactory::class,
                    'App\Pages\TestErrorModel' => InvokableFactory::class,
                    'App\Pages\TestPartialViewModel' => InvokableFactory::class,
                    'App\Pages\TestViewModel' => InvokableFactory::class,
                    'App\Pages\UrlModel' => InvokableFactory::class,
                    'App\Pages\WelcomeModel' => InvokableFactory::class,
                    'App\Pages\Templates\HeaderModel' => InvokableFactory::class,
                    'App\Pages\TestPartialViewModel' => InvokableFactory::class,
                ]
            ],
        ];
    }

    public function init(ModuleManager $moduleManager)
    {
        $events = $moduleManager->getEventManager();
        $sharedEvents = $events->getSharedManager();

        $sharedEvents->attach('Obullo\Application', 'test.init', function ($e) {
            return $e->getName();
        });
    }

    public function onBootstrap(PageEvent $e)
    {
        $application = $e->getApplication();
        $events = $application->getEventManager();

        $events->attach('test.onBootstrap', function ($e) {
            return $e->getName();
        });
    }

    public function onErrorHandler(PageEvent $e)
    {
        $container = $e->getApplication()->getContainer();

        $errorHandler = new ErrorHandler(
            $container->get('Request'),
            function () {
                return new Response;
            },
            $container->get('App\Middleware\ErrorResponseGenerator')
        );
        return $errorHandler;
    }

    public function onNotFoundHandler(PageEvent $e)
    {
        $container = $e->getApplication()->getContainer();

        $notFoundHandler = new NotFoundHandler(
            function () {
                return new Response;
            },
            $container->get('App\Middleware\NotFoundResponseGenerator')
        );
        return $notFoundHandler;
    }
}