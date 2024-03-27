<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes;

use Pentagonal\Sso\Core\Exceptions\RuntimeException;
use Pentagonal\Sso\Core\Routes\Exceptions\RouteErrorException;
use Pentagonal\Sso\Core\Routes\Exceptions\RouteHttpMethodNotAllowedException;
use Pentagonal\Sso\Core\Routes\Exceptions\RouteHttpNotFoundException;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteResultInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouterInterface;
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
     * @throws RouteHttpNotFoundException
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
        $status = $result->getRouteStatus();
        $route = $result->getRoute();
        if (!$route || $status === RouteResultInterface::NOT_FOUND) {
            throw new RouteHttpNotFoundException(
                $request
            );
        }
        if ($status === RouteResultInterface::FOUND) {
            return $routes->getRouteDispatcher()->dispatch(
                $route->getCallback(),
                $request,
                $routes->getResponseFactory(),
                $result->getMatchedParams(),
                $route
            );
        }
        if ($status === RouteResultInterface::METHOD_NOT_ALLOWED) {
            throw new RouteHttpMethodNotAllowedException(
                $request,
                $route
            );
        }
        throw new RouteErrorException(
            $request,
            $route,
            'There was an error when resolve routing'
        );
    }
}
