<?php
declare(strict_types=1);

namespace Pentagonal\Sso;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use League\Container\Container;
use Pentagonal\Sso\Routes\Interfaces\ControllerInterface;
use Pentagonal\Sso\Routes\Interfaces\RouteDispatcherInterface;
use Pentagonal\Sso\Routes\Interfaces\RouteInterface;
use Pentagonal\Sso\Routes\Interfaces\RouteMethodInterface;
use Pentagonal\Sso\Routes\Interfaces\RouterInterface;
use Pentagonal\Sso\Routes\Interfaces\RoutesInterface;
use Pentagonal\Sso\Routes\RouteHandler;
use Pentagonal\Sso\Routes\Traits\RouteMethodTrait;
use Pentagonal\Sso\Services\EventManager;
use Pentagonal\Sso\Services\Interfaces\EventManagerInterface;
use Pentagonal\Sso\Services\Interfaces\MiddlewareServiceDispatcherInterface;
use Pentagonal\Sso\Services\Interfaces\ResponseEmitterInterface;
use Pentagonal\Sso\Services\MiddlewareServiceDispatcher;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(?ServerRequestInterface $request = null) : ResponseInterface
    {
        $request ??= ServerRequest::fromGlobals();
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
        $emitter->emit($response);
        return $response;
    }
}