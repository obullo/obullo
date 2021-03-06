<?php

namespace Obullo;

use Psr\Http\Message\ServerRequestInterface as Request;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stratigility\MiddlewarePipe;

use Throwable;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;

/**
 * Main application class for invoking applications
 *
 * Expects the user will provide a configured ServiceManager, configured with
 * the following services:
 *
 * - EventManager
 * - ModuleManager
 * - Request
 * - Response
 * - RouteListener
 * - Router
 * - DispatchListener
 *
 * The most common workflow is:
 * <code>
 * $application = $container->get('Application');
 * $application->bootstrap(array $listeners);
 * $response = $app->run();
 * $response->send();
 * </code>
 *
 * bootstrap() opts in to the default route, dispatch, and view listeners,
 * sets up the PageEvent, and triggers the bootstrap event. This can be omitted
 * if you wish to setup your own listeners and/or workflow; alternately, you
 * can simply extend the class to override such behavior.
 */
class Application
{
    /**
     * Default application event listeners
     *
     * @var array
     */
    protected $defaultListeners = [
        'RouteListener',
        'DispatchListener',
    ];

    /**
     * @var MiddlewarePipe
     */
    protected $app;

    /**
     * @var app config array
     */
    protected $appConfig;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * @var PageEvent
     */
    protected $event;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Constructor
     *
     * @param ServiceManager        $serviceManager container
     * @param EventManagerInterface $events         events
     * @param Request               $request        request
     */
    public function __construct(
        ServiceManager $serviceManager,
        EventManagerInterface $events,
        Request $request
    ) {
        $this->serviceManager = $serviceManager;
        $this->setEventManager($events);
        $this->setRequest($request);
        $this->event = new PageEvent;
        $this->app = new MiddlewarePipe;
        $this->appConfig = $serviceManager->get('appConfig');
    }

    /**
     * Retrieve the application configuration
     *
     * @return array|object
     */
    public function getConfig()
    {
        return $this->serviceManager->get('config');
    }
    
    /**
     * Bootstrap app and attach listeners
     *
     * @param array $listeners List of listeners to attach.
     * @return void
     */
    public function bootstrap(array $listeners = [])
    {
        $this->event->setTarget($this);
        $this->event->setApplication($this);
        $this->event->setRequest($this->request);
        $this->event->setRouter($this->getContainer()->get('Router'));
        $this->event->setParam('app', $this->app);
        $this->event->setParam('middlewares', $this->appConfig['middlewares']);

        // setup default listeners
        //
        $listeners = array_unique(array_merge($this->defaultListeners, $listeners));
        foreach ($listeners as $listener) {
            $this->serviceManager->get($listener)->attach($this->events);
        }
        // trigger error handler
        //
        $this->event->setName(PageEvent::EVENT_ERROR_HANDLER);
        $result = $this->events->triggerEvent($this->event);
        $errorHandler = $result->last();
        $this->app->pipe($errorHandler);

        $this->event->setName(PageEvent::EVENT_ROUTE);
        $routeResult = $this->events->triggerEvent($this->event);

        // trigger middlewares event
        //
        $this->event->setName(PageEvent::EVENT_MIDDLEWARES);
        $this->events->triggerEvent($this->event);

        // trigger not found handler
        //
        $this->event->setName(PageEvent::EVENT_NOT_FOUND_HANDLER);
        $result = $this->events->triggerEvent($this->event);
        $notFoundHandler = $result->last();
        $this->app->pipe($notFoundHandler);

        // trigger bootstrap event
        //
        $this->event->setName(PageEvent::EVENT_BOOTSTRAP);
        $this->events->triggerEvent($this->event);
        return $this;
    }

    /**
     * Retrieve the service manager
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Alias of the service manager
     *
     * @return ServiceManager
     */
    public function getContainer()
    {
        return $this->getServiceManager();
    }

    /**
     * Set request
     *
     * @param object $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Returns to request
     *
     * @return object
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the event manager instance
     *
     * @param  EventManagerInterface $eventManager
     * @return Application
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        // To works with shared events we need to set identifiers, otherwise
        // we have to enable all shared events from Obullo\Container\ServiceManagerConfig file
        // using "'shared' => ['EventManager' => true]" option.

        $eventManager->setIdentifiers([
            __CLASS__,
            get_class($this),
        ]);
        $this->events = $eventManager;
        return $this;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        return $this->events;
    }

    /**
     * Get the Page event instance
     *
     * @return PageEvent
     */
    public function getPageEvent()
    {
        return $this->event;
    }

    /**
     * Alias of Page event instance
     *
     * @return PageEvent
     */
    public function getMvcEvent()
    {
        return $this->event;
    }

    /**
     * Run application and return response
     * without emmitting
     *
     * @return response
     */
    public function runWithoutEmit()
    {
        $callback = [$this->app, 'handle'];
        $response = $callback($this->request);

        $this->event->setResponse($response);
        return $response;
    }

    /**
     * Run the application
     */
    public function run()
    {
        $server = new RequestHandlerRunner(
            $this->app,
            new SapiEmitter(),
            function () {
                return $this->serviceManager->get('Request');
            },
            function (Throwable $e) {
                $response = (new ResponseFactory())->createResponse(500);
                $response->getBody()->write(sprintf(
                    'An error occurred: %s',
                    $e->getMessage
                ));
                $this->event->setResponse($response);
                return $response;
            }
        );
        $server->run();
    }
}
