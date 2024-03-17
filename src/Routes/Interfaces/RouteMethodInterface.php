<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes\Interfaces;

interface RouteMethodInterface
{
    /**
     * Add by controller
     *
     * @param ControllerInterface|string $controller
     * @return ?array null if not added
     */
    public function addController(ControllerInterface|string $controller) : ?array;

    /**
     * Add route by methods & pattern
     *
     * @param string|array<string> $methods
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function map(
        string|array $methods,
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ) : RouteInterface;

    /**
     * Add a group route
     *
     * @param string $pattern
     * @param callable $callback
     * @return RouterInterface
     */
    public function group(string $pattern, callable $callback) : RouterInterface;

    /**
     * Add route with get method
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function get(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;

    /**
     * Add route with post method
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function post(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;

    /**
     * Add route with put method
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function put(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;

    /**
     * Add route with patch method
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function patch(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;

    /**
     * Add route with delete method
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function delete(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;

    /**
     * Add route with options method
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function options(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;

    /**
     * Add route with head method
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function head(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;

    /**
     * Add route with trace method
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function trace(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;

    /**
     * Add route with connect method
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function connect(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;

    /**
     * Add route with any method
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function any(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;

    /**
     * Add route with all method
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @return RouteInterface
     */
    public function all(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;
}
