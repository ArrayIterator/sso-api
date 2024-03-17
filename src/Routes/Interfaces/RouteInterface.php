<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes\Interfaces;

interface RouteInterface
{
    public const DEFAULT_TOKENS = [
        'any' => '.+',
        'id' => '\d+',
        'hex' => '[a-f0-9]+',
        'alnum' => '[a-zA-Z0-9]+',
        'alpha' => '[a-zA-Z]+',
        'num' => '\d+',
        'slug' => '[a-z0-9-]+',
        'uuid' => '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}',
        'year' => '\d{4}',
        'month' => '0[1-9]|1[0-2]',
        'day' => '0[1-9]|[12][0-9]|3[01]',
        'date' => '\d{4}-\d{2}-\d{2}',
        'time' => '[01][0-9]|2[0-3]:[0-5][0-9]',
        'hour' => '[01][0-9]|2[0-3]',
        'minute' => '[0-5][0-9]',
        'second' => '[0-5][0-9]',
    ];

    public const DEFAULT_TOKEN = '[^/]+';

    /**
     * Default Method, the default method is GET
     */
    public const DEFAULT_METHOD = 'GET';

    /**
     * Wildcard Method
     */
    public const WILDCARD_METHOD = '*';

    /**
     * Any Method List
     */
    public const ANY_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
        'HEAD',
        'TRACE',
        'CONNECT',
    ];

    /**
     * RouteInterface constructor.
     *
     * @param string|array<string> $methods Method list of route
     * @param string $pattern Route Pattern
     * @param callable $callback Route Callback
     * @param ?string $name Route Name
     * @param ?int $priority Route Priority
     * @param ?string $host Route Host
     */
    public function __construct(
        string|array $methods,
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    );

    /**
     * @return ?string Route Name
     */
    public function getName() : ?string;

    /**
     * @return ?string Route Host
     */
    public function getHost() : ?string;

    /**
     * @return string Route Pattern
     */
    public function getPattern() : string;

    /**
     * @return array<string> Method list of route
     */
    public function getMethods() : array;

    /**
     * @return callable Route Callback
     */
    public function getCallback() : callable;

    /**
     * @return ?int Route Priority
     */
    public function getPriority() : ?int;

    /**
     * @param ?string $name Route Name
     * @return RouteInterface
     */
    public function setName(?string $name) : RouteInterface;

    /**
     * Set Host of Route
     *
     * @param ?string $host
     * @return RouteInterface
     */
    public function setHost(?string $host) : RouteInterface;

    /**
     * Set route priority
     *
     * @param ?int $priority
     * @return RouteInterface
     */
    public function setPriority(?int $priority) : RouteInterface;

    /**
     * Set param tokens
     *
     * @param array $tokens
     * @return RouteInterface
     */
    public function tokens(array $tokens) : RouteInterface;

    /**
     * Set param token
     *
     * @param string $key
     * @param string $pattern
     * @return RouteInterface
     */
    public function token(string $key, string $pattern) : RouteInterface;

    /**
     * Set route arguments
     *
     * @param array $variables
     * @return RouteInterface
     */
    public function setArguments(array $variables) : RouteInterface;

    /**
     * Check if route has argument
     * @param string $key
     * @return bool
     */
    public function hasArgument(string $key) : bool;

    /**
     * Set route argument
     * @param string $key
     * @param mixed $value
     * @return RouteInterface
     */
    public function setArgument(string $key, mixed $value) : RouteInterface;

    /**
     * Get route argument
     *
     * @param string $key
     * @return mixed
     */
    public function getArgument(string $key) : mixed;

    /**
     * Get route arguments
     *
     * @return array
     */
    public function getArguments() : array;

    /**
     * Get route arguments
     *
     * @param string $key
     * @return RouteInterface
     */
    public function removeArgument(string $key) : RouteInterface;

    /**
     * @return string Compiled Pattern
     */
    public function getCompiledPattern() : string;

    /**
     * Check if route contains method
     * If route method is "*" make sure it was true
     *
     * @param string $method
     * @return bool
     */
    public function isAllowedMethod(string $method) : bool;

    /**
     * @param string|array $methods
     * @return array
     */
    public function filterMethods(string|array $methods): array;
}
