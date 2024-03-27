<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use League\Container\Container;
use Pentagonal\Sso\Core\Formatters\Interfaces\JsonFormatterInterface;
use Pentagonal\Sso\Core\Formatters\Json;
use Pentagonal\Sso\Core\Handlers\ExceptionHandler;
use Pentagonal\Sso\Core\Handlers\Interfaces\ExceptionHandlerInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\ControllerInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteDispatcherInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteMethodInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouterInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RoutesInterface;
use Pentagonal\Sso\Core\Routes\RouteDispatcher;
use Pentagonal\Sso\Core\Routes\RouteHandler;
use Pentagonal\Sso\Core\Routes\Router;
use Pentagonal\Sso\Core\Routes\Routes;
use Pentagonal\Sso\Core\Routes\RoutingMiddleware;
use Pentagonal\Sso\Core\Routes\Traits\RouteMethodTrait;
use Pentagonal\Sso\Core\Services\EventManager;
use Pentagonal\Sso\Core\Services\Interfaces\EventManagerInterface;
use Pentagonal\Sso\Core\Services\Interfaces\MiddlewareServiceDispatcherInterface;
use Pentagonal\Sso\Core\Services\Interfaces\ResponseEmitterInterface;
use Pentagonal\Sso\Core\Services\MiddlewareServiceDispatcher;
use Pentagonal\Sso\Core\Services\ResponseEmitter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use function is_array;
use function next;
use function reset;

class Service implements RouteMethodInterface
{
    use RouteMethodTrait;

    /**
     * The container components
     *
     * @var array
     */
    final public const COMPONENTS = [
        ContainerInterface::class => Container::class,
        EventManagerInterface::class => EventManager::class,
        RequestHandlerInterface::class => [
            RouteHandler::class,
            [
                RouterInterface::class
            ]
        ],
        ResponseFactoryInterface::class => HttpFactory::class,
        StreamFactoryInterface::class => HttpFactory::class,
        RouteDispatcherInterface::class => RouteDispatcher::class,
        MiddlewareServiceDispatcherInterface::class => [
            MiddlewareServiceDispatcher::class,
            [
                ContainerInterface::class,
                RequestHandlerInterface::class
            ]
        ],
        RoutesInterface::class => [
            Routes::class,
            [
                ContainerInterface::class,
                EventManagerInterface::class,
                ResponseFactoryInterface::class,
                RouteDispatcherInterface::class
            ]
        ],
        RouterInterface::class => [
            Router::class,
            [
                RoutesInterface::class
            ]
        ],
        ResponseEmitterInterface::class => [
            ResponseEmitter::class,
            [
                EventManagerInterface::class
            ]
        ],
        Service::class => [
            Service::class,
            [
                RouterInterface::class,
                EventManagerInterface::class,
                MiddlewareServiceDispatcherInterface::class,
            ]
        ],
        JsonFormatterInterface::class => [
            Json::class,
            [
                EventManagerInterface::class
            ]
        ]
    ];

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

    /**
     * @var MiddlewareServiceDispatcherInterface
     */
    private MiddlewareServiceDispatcherInterface $middlewareDispatcher;

    /**
     * @var ExceptionHandlerInterface
     */
    private ExceptionHandlerInterface $handler;

    /**
     * Service constructor.
     */
    public function __construct(
        ?RouterInterface $router = null,
        ?EventManagerInterface $eventManager = null,
        ?MiddlewareServiceDispatcherInterface $dispatcher = null
    ) {
        $container = new Container();
        $this->registerDefaultComponents($container, $router, $eventManager, $dispatcher);
        $router ??= $container->get(RouterInterface::class);
        $eventManager ??= $container->get(EventManagerInterface::class);
        $dispatcher ??= $container->get(MiddlewareServiceDispatcherInterface::class);
        $routeHandler = $container->get(RequestHandlerInterface::class);

        $this->router = $router;
        $this->middlewareDispatcher = $dispatcher->setStack($routeHandler);
        $this->container = $container;
        $this->eventManager = $eventManager;
    }

    /**
     * Register default components
     *
     * @param Container $container
     * @param RouterInterface|null $router
     * @param EventManagerInterface|null $eventManager
     * @param MiddlewareServiceDispatcherInterface|null $dispatcher
     */
    private function registerDefaultComponents(
        Container $container,
        ?RouterInterface $router = null,
        ?EventManagerInterface $eventManager = null,
        ?MiddlewareServiceDispatcherInterface $dispatcher = null
    ): void {

        // trigger before register
        $eventManager?->trigger('service.components.register.before');

        $components = self::COMPONENTS;
        $components[ContainerInterface::class] = $container;
        if ($router) {
            $components[RouterInterface::class] = $router;
        }
        if ($eventManager) {
            $components[EventManagerInterface::class] = $eventManager;
        }
        if ($dispatcher) {
            $components[MiddlewareServiceDispatcherInterface::class] = $dispatcher;
        }

        foreach ($components as $key => $value) {
            if (is_array($value)) {
                $className = reset($value);
                $args = next($value);
                $definition = $container->addShared($key, $className);
                if (is_array($args)) {
                    $definition->addArguments($args);
                } else {
                    $definition->addArgument($args);
                }
                continue;
            }
            $container->addShared($key, $value);
        }

        $container
            ->get(EventManagerInterface::class)
            ->trigger(
                'service.components.register.after',
                $container
            );
    }

    /**
     * Get Router
     *
     * @return RouterInterface
     */
    public function getRouter() : RouterInterface
    {
        return $this->router;
    }

    /**
     * Get Middleware Dispatcher
     *
     * @return MiddlewareServiceDispatcherInterface
     */
    public function getMiddlewareDispatcher() : MiddlewareServiceDispatcherInterface
    {
        return $this->middlewareDispatcher;
    }

    /**
     * Get Event Manager
     *
     * @return EventManagerInterface
     */
    public function getEventManager() : EventManagerInterface
    {
        return $this->eventManager;
    }

    /**
     * @return ExceptionHandlerInterface
     */
    public function getExceptionHandler() : ExceptionHandlerInterface
    {
        return $this->handler ??= new ExceptionHandler();
    }

    /**
     * @return $this
     */
    public function addRoutingMiddleware(): static
    {
        $middleware = new RoutingMiddleware($this->router->getRoutes());
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
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function run(?ServerRequestInterface $request = null) : ResponseInterface
    {
        $request ??= ServerRequest::fromGlobals();
        try {
            $request = $request->withAttribute(
                'service.worker.start',
                microtime(true)
            );
            $this->getEventManager()?->trigger(
                'service.worker.start',
                $this
            );
            $container = $this->getContainer();
            $request = $request->withAttribute('container', $container);
            $exceptionHandler = $this->getExceptionHandler();

            try {
                $response = $this->middlewareDispatcher->handle($request);
            } catch (Throwable $exception) {
                $response = $exceptionHandler->handle($request, $exception);
            }

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
                'service.worker.end',
                $this,
                $response ?? null
            );
        }
        $emitter->emit($response);
        return $response;
    }
}
