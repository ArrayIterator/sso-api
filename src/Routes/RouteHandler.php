<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes;

use Pentagonal\Sso\Exceptions\RuntimeException;
use Pentagonal\Sso\Routes\Exceptions\RouteMethodNotAllowedException;
use Pentagonal\Sso\Routes\Exceptions\RouteNotFoundException;
use Pentagonal\Sso\Routes\Interfaces\RouteResultInterface;
use Pentagonal\Sso\Routes\Interfaces\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class RouteHandler implements RequestHandlerInterface
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    /**
     * @throws RouteNotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routes = $this->router->getRoutes();
        $result = $request->getAttribute(RouteContext::ROUTE_RESULT);
        if (!$result instanceof RouteResultInterface) {
            $request = $routes->performRouting($request);
            $result = $request->getAttribute(RouteContext::ROUTE_RESULT);
        }

        if (!$result instanceof RouteResultInterface) {
            throw new RuntimeException(
                'Can not get route result!'
            );
        }

        $request = $result->getRequest();
        $route = $result->getRoute();
        switch ($result->getRouteStatus()) {
            case RouteResultInterface::FOUND:
                return $routes->getRouteDispatcher()->dispatch(
                    $route->getCallback(),
                    $request,
                    $routes->getResponseFactory(),
                    $result->getMatchedParams(),
                    $route
                );
            case RouteResultInterface::METHOD_NOT_ALLOWED:
                throw new RouteMethodNotAllowedException(
                    $request,
                    $route
                );
            case RouteResultInterface::NOT_FOUND:
                throw new RouteNotFoundException(
                    $request
                );
            default:
                throw new RuntimeException(
                    'There was an error when resolve routing'
                );
        }
    }
}
