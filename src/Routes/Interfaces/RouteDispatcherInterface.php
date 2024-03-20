<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Interfaces;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RouteDispatcherInterface
{
    /**
     * Dispatch Route
     *
     * @param callable $callback
     * @param ServerRequestInterface $request
     * @param ResponseFactoryInterface $responseFactory
     * @param array $params
     * @param RouteInterface|null $route
     * @return ResponseInterface
     */
    public function dispatch(
        callable $callback,
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        array $params = [],
        ?RouteInterface $route = null
    ) : ResponseInterface;
}
