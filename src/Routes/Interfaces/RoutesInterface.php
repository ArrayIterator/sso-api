<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Interfaces;

use Countable;
use Pentagonal\Sso\Core\Services\Interfaces\EventManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RoutesInterface extends Countable
{
    /**
     * Set Container
     *
     * @return ?ContainerInterface
     */
    public function getContainer() : ?ContainerInterface;

    /**
     * Set Event Manager
     *
     * @param EventManagerInterface|null $manager
     */
    public function setEventManager(?EventManagerInterface $manager);

    /**
     * Get Event Manager
     * @return ?EventManagerInterface
     */
    public function getEventManager() : ?EventManagerInterface;

    /**
     * Set Response Factory
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public function setResponseFactory(ResponseFactoryInterface $responseFactory);

    /**
     * Get Response Factory
     *
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory() : ResponseFactoryInterface;

    /**
     * Set Route Dispatcher
     *
     * @param RouteDispatcherInterface $routeDispatcher
     */
    public function setRouteDispatcher(RouteDispatcherInterface $routeDispatcher);

    /**
     * Get Route Dispatcher
     *
     * @return RouteDispatcherInterface
     */
    public function getRouteDispatcher() : RouteDispatcherInterface;

    /**
     * Add Route
     *
     * @param RouteInterface $route
     */
    public function add(RouteInterface $route);

    /**
     * Remove route
     *
     * @param RouteInterface $route
     */
    public function removeRoute(RouteInterface $route);

    /**
     * Check if route exist
     *
     * @param RouteInterface $route
     * @return bool
     */
    public function hasRoute(RouteInterface $route) : bool;

    /**
     * @param string $pattern
     * @param string|array|null $methods
     * @param ?string|null $host
     * @return ?array<RouteInterface>
     */
    public function remove(
        string $pattern,
        string|array $methods = null,
        ?string $host = null
    ) : ?array;

    /**
     * Check if route exist
     *
     * @param string $pattern
     * @param string|array|null $methods
     * @param string|null $host
     * @return bool
     */
    public function has(
        string $pattern,
        string|array $methods = null,
        ?string $host = null
    ) : bool;

    /**
     * Clear All Routes
     */
    public function clear();

    /**
     * Check if route match
     *
     * @param ServerRequestInterface $request
     * @return RouteResultInterface
     */
    public function match(ServerRequestInterface $request) : RouteResultInterface;

    /**
     * Perform Routing
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function performRouting(ServerRequestInterface $request): ServerRequestInterface;
}
