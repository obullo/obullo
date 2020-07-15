<?php

declare(strict_types=1);

namespace Obullo\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Obullo\Router\Router;
use Laminas\Diactoros\Response\TextResponse;

class HttpMethodMiddleware implements MiddlewareInterface
{
    protected $router;
    
    /**
    * Constructor
    *
    * @param Router     $router     router
    * @param Translator $translator translator
    */
    public function __construct(Router $router)
    {
        $this->router = $router;
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
        if ($this->router->hasMatch()) {
            $allowedMethods = $this->router->getMatchedRoute()
                ->getMethods();
            if (! in_array($request->getMethod(), $allowedMethods)) {
                $message = sprintf(
                    'Only Http %s Methods Allowed',
                    implode(', ', $allowedMethods)
                );
                return new TextResponse($message, 405);
            }
        }
        return $handler->handle($request);
    }
}
