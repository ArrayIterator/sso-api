<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes;

use Pentagonal\Sso\Routes\Attributes\Group;
use Pentagonal\Sso\Routes\Attributes\Interfaces\AttributeRouteInterface;
use Pentagonal\Sso\Routes\Interfaces\ControllerInterface;
use Pentagonal\Sso\Routes\Interfaces\RouteGroupInterface;
use Pentagonal\Sso\Routes\Interfaces\RouteInterface;
use Pentagonal\Sso\Routes\Interfaces\RouteMethodInterface;
use Pentagonal\Sso\Routes\Interfaces\RouterInterface;
use Pentagonal\Sso\Routes\Interfaces\RoutesInterface;
use Pentagonal\Sso\Routes\Traits\RouteMethodTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionUnionType;
use Stringable;
use Throwable;
use function is_a;
use function is_string;
use function spl_object_hash;
use function str_starts_with;
use function strtolower;

class Router implements RouterInterface
{
    use RouteMethodTrait;

    /**
     * @var string base path
     */
    protected string $basePath = '';

    /**
     * @var RoutesInterface $routes
     */
    private RoutesInterface $routes;

    /**
     * Route group
     */
    private ?RouteGroupInterface $group = null;

    /**
     * Router constructor.
     *
     * @param RoutesInterface $routeCollections
     */
    public function __construct(
        RoutesInterface $routeCollections,
    ) {
        $this->routes = $routeCollections;
    }

    /**
     * @inheritDoc
     */
    public function getRoutes() : RoutesInterface
    {
        return $this->routes;
    }

    /**
     * @inheritDoc
     */
    public function setBasePath(string $basePath): RouterInterface
    {
        $this->basePath = $basePath;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Add route by method of controller
     *
     * @param RouteMethodInterface $routeMethod
     * @param ReflectionClass $ref
     * @param ControllerInterface $controller
     * @param $routes
     * @return void
     * @internal for internal use only
     * @see Router::addController
     */
    private function addRouteByMethodInternal(
        RouteMethodInterface $routeMethod,
        ReflectionClass $ref,
        ControllerInterface $controller,
        &$routes
    ) : void {
        foreach ($ref->getMethods() as $method) {
            // accept public only
            if (!$method->isPublic()) {
                continue;
            }
            // magic method lists
            $methodName = $method->getName();
            // we do not support magic method
            if (str_starts_with($methodName, '__')
                // get container is prohibited
                || strtolower($methodName) === 'getcontainer'
            ) {
                continue;
            }
            // check return type
            $returnType = $method->getReturnType();
            if ($returnType) {
                $returnType = $returnType instanceof ReflectionUnionType
                    ? $returnType->getTypes()
                    : [$returnType];
                $continue = false;
                // check that should be a response interface or string
                foreach ($returnType as $type) {
                    $name = $type->getName();
                    // allow string & mixed
                    if ($name === 'string'
                        || $name === 'mixed'
                        || $name === 'void' // < using echo
                        || $name === ResponseInterface::class
                        || $name === Stringable::class
                        || is_a(ResponseInterface::class, $name)
                        // stream interface also string-able
                        || is_a(Stringable::class, $name)
                    ) {
                        $continue = true;
                        break;
                    }
                }
                if (!$continue) {
                    return;
                }
            }

            $attribute = $method->getAttributes(
                AttributeRouteInterface::class,
                ReflectionAttribute::IS_INSTANCEOF
            )[0]??null;
            if (!$attribute) {
                continue;
            }
            $attribute = $attribute->newInstance();
            /**
             * @var AttributeRouteInterface $attribute
             */
            $route = $routeMethod->map(
                $attribute->getMethods(),
                $attribute->getPattern(),
                [
                    $controller,
                    $method->getName()
                ],
                $attribute->getName(),
                $attribute->getPriority(),
                $attribute->getHost()
            )->setArguments($attribute->getArguments());
            $routes[spl_object_hash($route)] = $route;
        }
    }

    /**
     * @inheritDoc
     */
    public function addController(ControllerInterface|string $controller): ?array
    {
        try {
            $ref = new ReflectionClass($controller);
            if (!$ref->implementsInterface(ControllerInterface::class)) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }
        $controller = is_string($controller) ? new $controller(
            $this->routes->getContainer()
        ) : $controller;
        $group = $ref->getAttributes(
            Group::class,
            ReflectionAttribute::IS_INSTANCEOF
        )[0]??null;
        if ($group) {
            $group = $group->newInstance();
            /**
             * @var Group $group
             */
            $routes = [];
            $this->group(
                $group->getPattern(),
                function (RouteGroup $group) use ($ref, $controller, &$routes) {
                    $this->addRouteByMethodInternal(
                        $group,
                        $ref,
                        $controller,
                        $routes
                    );
                }
            );
        } else {
            $this->addRouteByMethodInternal(
                $this,
                $ref,
                $controller,
                $routes
            );
        }

        return empty($routes) ? null : $routes;
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
        $route = new Route(
            $methods,
            $pattern,
            $callback,
            $name,
            $priority,
            $host
        );
        $this->routes->add($route);
        return $route;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentGroup(): ?RouteGroupInterface
    {
        return $this->group;
    }

    /**
     * @inheritDoc
     */
    public function group(string $pattern, callable $callback): RouterInterface
    {
        // store the previous
        $previous = $this->getCurrentGroup();
        $pattern = ($previous?->getPattern()??'') . $pattern;
        $this->group = new RouteGroup(
            $this,
            $pattern,
            $callback,
            $previous
        );
        $this->group->dispatch();
        $this->group = $previous;
        return $this;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute(RouteContext::BASEPATH, $this->getBasePath());
        return $handler->handle($request);
    }
}
