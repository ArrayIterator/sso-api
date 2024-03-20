<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Interfaces;

use Psr\Http\Message\ServerRequestInterface;

interface RouteResultInterface
{
    /**
     * Route Status Code for Route Result Found
     */
    public const FOUND = 200;

    /**
     * Route Status Code for Route Result Not Found
     */
    public const NOT_FOUND = 404;

    /**
     * Route Status Code for Route Result Method Not Allowed
     */
    public const METHOD_NOT_ALLOWED = 405;

    public function __construct(
        ServerRequestInterface $request,
        RouteDispatcherInterface $routeDispatcher,
        int $routeStatus,
        array $matchesParams = [],
        ?RouteInterface $route = null
    );

    /**
     * Get Route Dispatcher
     *
     * @return RouteDispatcherInterface
     */
    public function getDispatcher() : RouteDispatcherInterface;

    /**
     * Get Request
     *
     * @return ServerRequestInterface
     */
    public function getRequest() : ServerRequestInterface;

    /**
     * Get Route Status
     *
     * @return int
     */
    public function getRouteStatus() : int;

    /**
     * Get Matched Params
     *
     * @return array
     */
    public function getMatchedParams() : array;

    /**
     * Get Route
     *
     * @return RouteInterface|null
     */
    public function getRoute() : ?RouteInterface;

    /**
     * Check if Route is Found
     *
     * @return bool
     */
    public function isFound() : bool;
}
