<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use League\Container\Container;
use Pentagonal\Sso\Core\Routes\Interfaces\ControllerInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteDispatcherInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteMethodInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouterInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RoutesInterface;
use Pentagonal\Sso\Core\Routes\RouteHandler;
use Pentagonal\Sso\Core\Routes\Traits\RouteMethodTrait;
use Pentagonal\Sso\Core\Services\EventManager;
use Pentagonal\Sso\Core\Services\Interfaces\EventManagerInterface;
use Pentagonal\Sso\Core\Services\Interfaces\MiddlewareServiceDispatcherInterface;
use Pentagonal\Sso\Core\Services\Interfaces\ResponseEmitterInterface;
use Pentagonal\Sso\Core\Services\MiddlewareServiceDispatcher;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class Service implements RouteMethodInterface
{
    use RouteMethodTrait;

    /**
     * @var EventManagerInterface
     */
    private EventManagerInterface $eventManager;

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    private MiddlewareServiceDispatcherInterface $middlewareDispatcher;

    /**
     * Service constructor.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(
        ?ContainerInterface $container = null,
        ?RouterInterface $router = null,
        ?EventManagerInterface $eventManager = null,
        ?MiddlewareServiceDispatcherInterface $dispatcher = null
    ) {
        $container = $container ?? new Container();
        $router ??= $container->has(
            RouterInterface::class
        ) ? $container->get(
            RouterInterface::class
        ) : null;
        $eventManager ??= $container->has(
            EventManagerInterface::class
        ) ? $container->get(
            EventManagerInterface::class
        ) : null;
        $routeDispatcher = $container->has(
            RouteDispatcherInterface::class
        ) ? $container->get(
            RouteDispatcherInterface::class
        ) : null;
        if (!$routeDispatcher instanceof RouteDispatcherInterface) {
            $routeDispatcher = new Routes\RouteDispatcher($container);
        }
        $responseFactory = $container->has(
            ResponseFactoryInterface::class
        ) ? $container->get(
            ResponseFactoryInterface::class
        ) : null;
        if (!$responseFactory instanceof ResponseFactoryInterface) {
            $responseFactory = new HttpFactory();
        }
        if (!$router instanceof RouterInterface) {
            $routes = $container->has(RoutesInterface::class)
                ? $container->get(RoutesInterface::class)
                : null;
            if (!$routes instanceof RoutesInterface) {
                $routes = new Routes\Routes(
                    $container,
                    $eventManager,
                    $responseFactory,
                    $routeDispatcher
                );
            }
            $router = new Routes\Router($routes);
        }

        if (!$eventManager instanceof EventManagerInterface) {
            $eventManager = $router->getRoutes()->getEventManager();
            if (!$eventManager) {
                $eventManager = new EventManager();
                $router->getRoutes()->setEventManager($eventManager);
            }
        }

        if (!$container->has(EventManagerInterface::class)) {
            $container->add(EventManagerInterface::class, $eventManager);
        }
        if (!$container->has(RouterInterface::class)) {
            $container->add(RouterInterface::class, $router);
        }
        if (!$container->has(ResponseFactoryInterface::class)) {
            $container->add(ResponseFactoryInterface::class, $responseFactory);
        }

        $routeHandler = new RouteHandler($router);
        if (!$dispatcher) {
            $dispatcher = new MiddlewareServiceDispatcher($routeHandler, $container);
        }

        $this->router = $router;
        $this->middlewareDispatcher = $dispatcher->setStack($routeHandler);
        $this->container = $container;
        $this->eventManager = $eventManager;
    }
    public function getRouter() : RouterInterface
    {
        return $this->router;
    }

    public function getMiddlewareDispatcher() : MiddlewareServiceDispatcherInterface
    {
        return $this->middlewareDispatcher;
    }

    public function getEventManager() : EventManagerInterface
    {
        return $this->eventManager;
    }

    /**
     * @return $this
     */
    public function addRoutingMiddleware(): static
    {
        $middleware = new Routes\RoutingMiddleware(
            $this->router->getRoutes()
        );
        return $this->add($middleware);
    }

    /**
     * @param callable|MiddlewareInterface $middleware
     * @return Service
     */
    public function add(callable|MiddlewareInterface $middleware): static
    {
        $this->getMiddlewareDispatcher()->add($middleware);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addController(ControllerInterface|string $controller): ?array
    {
        return $this->router->addController($controller);
    }

    /**
     * @inheritDoc
     */
    public function map(
        array|string $methods,
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->router->map(
            $methods,
            $pattern,
            $callback,
            $name,
            $priority,
            $host
        );
    }

    /**
     * @inheritDoc
     */
    public function group(string $pattern, callable $callback) : RouterInterface
    {
        return $this->router->group($pattern, $callback);
    }

    public function getContainer() : ContainerInterface
    {
        return $this->container;
    }

    /**
     * Run the application
     *
     * @param ?ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function run(?ServerRequestInterface $request = null) : ResponseInterface
    {
        $request ??= ServerRequest::fromGlobals();
        try {
            $request = $request->withAttribute(
                'worker_start',
                microtime(true)
            );
            $this->getEventManager()?->trigger(
                'worker.start',
                $this
            );
            $container = $this->getContainer();
            $request = $request->withAttribute('container', $container);
            $response = $this->middlewareDispatcher->handle($request);
            $emitter = $container->has(ResponseEmitterInterface::class)
                ? $container->get(ResponseEmitterInterface::class)
                : null;
            $emitter = $emitter instanceof ResponseEmitterInterface
                ? $emitter
                : new Services\ResponseEmitter(
                    $this->eventManager
                );
        } finally {
            $this->getEventManager()?->trigger(
                'worker.end',
                $this,
                $response ?? null
            );
        }
        $emitter->emit($response);
        return $response;
    }
}
