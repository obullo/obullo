<?php

namespace Obullo\Middleware;

use Obullo\Router\Router;
use Zend\View\View;
use Zend\View\Model\ViewModel;
use Obullo\Container\ContainerAwareInterface;
use Obullo\Container\ContainerAwareTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;

use Throwable;
use Exception;

class PageHandler implements MiddlewareInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Container
     *
     * @var object
     */
    protected $route;

    /**
     * Constructor
     *
     * @param ContainerInterface $container container
     */
    public function __construct(Router $router)
    {
        $this->route = $router->getMatchedRoute();
    }

    /**
     * Process
     *
     * @param  ServerRequestInterface  $request request
     * @param  RequestHandlerInterface $handler request handler
     *
     * @return object|exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $container = $this->getContainer();
        try {
            $level = ob_get_level();
            
            $handlerClass = $this->route->getHandler();
            $pageModel = new $handlerClass;

            $method = $request->getMethod();
            $methodName = 'on'.ucfirst(strtolower($method));
            $response = $pageModel->$methodName($request);

            if ($response instanceof ViewModel) {
                $view = $container->get(View::class);
                $templateName = $response->getTemplate();
                if (empty($templateName)) {
                    $templateName = substr(strrchr(get_class($pageModel), "\\"), 1).'.phtml';
                    $response->setTemplate($templateName);
                }
                return $view->render($response);
            }

            return $response;

            // return new TextResponse(
            //     sprintf(
            //         'The page "%s" does not exists.',
            //         $page,
            //         405
            //     )
            // );
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        } catch (Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
    }

    /**
     * Call plugin methods
     *
     * @param  string $method name
     * @param  array  $args   arguments
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->getContainer()->get('plugin')->$method(...$args);
    }

    /**
     * Get class from plugin manager
     *
     * @param  string $class class
     * @return object
     */
    public function plugin($class)
    {
        return $this->getContainer()->get('plugin')->get($class);
    }
}
