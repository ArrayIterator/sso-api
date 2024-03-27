<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes;

use GuzzleHttp\Psr7\HttpFactory;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteDispatcherInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteResultInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RoutesInterface;
use Pentagonal\Sso\Core\Services\Interfaces\EventManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use function addcslashes;
use function in_array;
use function preg_match;
use function sort;
use function spl_object_hash;
use function strtolower;
use function strtoupper;
use function trim;
use function uasort;
use const PREG_NO_ERROR;

class Routes implements RoutesInterface
{
    /**
     * @var array<RouteInterface> $routes
     */
    protected array $routes = [];

    /**
     * @var ?EventManagerInterface
     */
    private ?EventManagerInterface $manager = null;

    /**
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    /**
     * @var RouteDispatcherInterface $routeDispatcher
     */
    private RouteDispatcherInterface $routeDispatcher;

    /**
     * @var ?ContainerInterface
     */
    private ?ContainerInterface $container;

    /**
     * @param ContainerInterface|null $container
     * @param ?EventManagerInterface $manager
     * @param ?ResponseFactoryInterface $responseFactory
     * @param RouteDispatcherInterface|null $routeDispatcher
     */
    public function __construct(
        ?ContainerInterface $container = null,
        ?EventManagerInterface $manager = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?RouteDispatcherInterface $routeDispatcher = null
    ) {
        if (!$responseFactory) {
            try {
                if ($container->has(ResponseFactoryInterface::class)) {
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                }
            } catch (Throwable) {
                $responseFactory = null;
            }
            if (!$responseFactory instanceof ResponseFactoryInterface) {
                $responseFactory = new HttpFactory();
            }
        }
        if (!$routeDispatcher) {
            try {
                if ($container->has(RouteDispatcherInterface::class)) {
                    $routeDispatcher = $container->get(RouteDispatcherInterface::class);
                }
            } catch (Throwable) {
                $routeDispatcher = null;
            }
            if (!$routeDispatcher instanceof RouteDispatcherInterface) {
                $routeDispatcher = new RouteDispatcher($container);
            }
        }
        $this->container = $container;
        $this->setEventManager($manager);
        $this->setResponseFactory($responseFactory);
        $this->setRouteDispatcher($routeDispatcher);
    }

    public function getContainer() : ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function setEventManager(?EventManagerInterface $manager): void
    {
        $this->manager = $manager;
    }

    /**
     * @inheritDoc
     */
    public function getEventManager(): ?EventManagerInterface
    {
        return $this->manager;
    }

    /**
     * @param ResponseFactoryInterface $responseFactory
     * @return void
     */
    public function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * @inheritDoc
     */
    public function setRouteDispatcher(RouteDispatcherInterface $routeDispatcher): void
    {
        $this->routeDispatcher = $routeDispatcher;
    }

    /**
     * @inheritDoc
     */
    public function getRouteDispatcher(): RouteDispatcherInterface
    {
        return $this->routeDispatcher;
    }

    private function triggerEvent(string $eventName, ...$arguments): void
    {
        $this->manager?->trigger($eventName, ...$arguments);
    }

    /**
     * @inheritDoc
     */
    public function add(RouteInterface $route): RoutesInterface
    {
        $this->triggerEvent('route.add', $route);
        $this->routes[spl_object_hash($route)] = $route;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function removeRoute(RouteInterface $route): bool
    {
        $id = spl_object_hash($route);
        if (isset($this->routes[$id])) {
            unset($this->routes[$id]);
            $this->triggerEvent('route.remove', $route);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasRoute(RouteInterface $route): bool
    {
        return isset($this->routes[spl_object_hash($route)]);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $pattern, array|string $methods = null, ?string $host = null): ?array
    {
        $removedRoutes = [];
        foreach ($this->routes as $route) {
            // if pattern mismatched
            if ($route->getPattern() !== $pattern) {
                continue;
            }
            // if host mismatched
            if ($host !== null && $route->getHost() !== $host) {
                continue;
            }
            if ($methods !== null) {
                $methods = $route->filterMethods($methods);
                $routeMethods = $route->getMethods();
                sort($methods);
                sort($routeMethods);
                // if it does not contain wildcard method & methods is mismatched
                if (!in_array(
                    RouteInterface::WILDCARD_METHOD,
                    $methods,
                    true
                ) && $methods !== $routeMethods) {
                    continue;
                }
            }

            $removedRoutes[] = $route;
            $this->removeRoute($route);
        }

        return empty($removedRoutes) ? null : $removedRoutes;
    }

    /**
     * @inheritDoc
     */
    public function has(string $pattern, array|string $methods = null, ?string $host = null): bool
    {
        foreach ($this->routes as $route) {
            // if pattern mismatched
            if ($route->getPattern() !== $pattern
                || ($host !== null && $route->getHost() !== $host)
            ) {
                continue;
            }
            if ($methods === null) {
                return true;
            }
            $methods = $route->filterMethods($methods);
            $routeMethods = $route->getMethods();
            sort($methods);
            sort($routeMethods);
            // if it does not contain wildcard method & methods is mismatched
            if (!in_array(
                RouteInterface::WILDCARD_METHOD,
                $methods,
                true
            ) && $methods !== $routeMethods) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->routes = [];
    }

    /**
     * @inheritDoc
     */
    public function match(ServerRequestInterface $request): RouteResultInterface
    {
        $this->triggerEvent('route.match', $request);
        // sort by priority
        uasort($this->routes, static function (RouteInterface $a, RouteInterface $b) {
            $priorityA = $a->getPriority();
            $priorityB = $b->getPriority();
            if ($priorityA === $priorityB) {
                return 0;
            }
            if ($priorityA === null) {
                return 1;
            }
            if ($priorityB === null) {
                return -1;
            }
            return $priorityA > $priorityB ? -1 : 1;
        });

        // get base path
        $basePath = $request->getAttribute(RouteContext::BASEPATH);
        $basePath = is_string($basePath) ? $basePath : '';
        $path   = $request->getUri()->getPath();
        if ($basePath !== '') {
            $path = substr($path, strlen($basePath)) ?: '/';
        }
        $host   = strtolower($request->getUri()->getHost());
        $method = strtoupper(trim($request->getMethod()));
        $matchedRouteNotAllowed = null;
        $matchesRoutedNotAllowed = null;
        foreach ($this->routes as $route) {
            $routeHost = $route->getHost();
            if ($routeHost && strtolower($routeHost) !== $host) {
                continue;
            }
            $allowedMethod = $route->isAllowedMethod($method);
            $compiledPattern = $route->getCompiledPattern();
            $compiledPattern = $compiledPattern === '' ? RouteInterface::DEFAULT_TOKEN : $compiledPattern;
            $compiledPattern = addcslashes($compiledPattern, '#');
            if (!preg_match('#^' . $compiledPattern . '$#', $path, $matches, PREG_NO_ERROR)) {
                continue;
            }
            if (!$allowedMethod) {
                $matchesRoutedNotAllowed ??= $matches;
                $matchedRouteNotAllowed ??= $route;
                continue;
            }
            $request = $request->withAttribute(
                RouteContext::ROUTE,
                $route
            );
            $result = new RouteResult(
                $request,
                $this->getRouteDispatcher(),
                RouteResultInterface::FOUND,
                $matches,
                $route
            );
            $this->triggerEvent(
                'route.matched',
                $result->getRequest(),
                $matches,
                $route
            );
            return $result;
        }

        if ($matchedRouteNotAllowed) {
            $result = new RouteResult(
                $request->withAttribute(
                    RouteContext::ROUTE,
                    $matchedRouteNotAllowed
                ),
                $this->getRouteDispatcher(),
                RouteResultInterface::METHOD_NOT_ALLOWED,
                $matchesRoutedNotAllowed ?? [],
                $matchedRouteNotAllowed
            );
            $this->triggerEvent(
                'route.method_not_allowed',
                $result->getRequest(),
                $matchedRouteNotAllowed
            );
            return $result;
        }

        $result = new RouteResult(
            $request,
            $this->getRouteDispatcher(),
            RouteResultInterface::NOT_FOUND,
            [],
            null
        );
        $this->triggerEvent('route.not_found', $result->getRequest(), $result);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * @inheritDoc
     */
    public function performRouting(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withAttribute(
            RouteContext::ROUTE_RESULT,
            $this->match($request)
        );
    }
}
