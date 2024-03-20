<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Traits;

use Pentagonal\Sso\Core\Routes\Interfaces\ControllerInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteInterface;

trait RouteMethodTrait
{
    /**
     * @inheritDoc
     */
    abstract public function addController(ControllerInterface|string $controller) : ?array;

    /**
     * @inheritDoc
     */
    abstract public function map(
        array|string $methods,
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface;

    /**
     * @inheritDoc
     */
    public function get(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->map(
            'GET',
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
    public function post(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->map(
            'POST',
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
    public function put(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->map(
            'PUT',
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
    public function patch(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->map(
            'PATCH',
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
    public function delete(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->map(
            'DELETE',
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
    public function options(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->map(
            'OPTIONS',
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
    public function head(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->map(
            'HEAD',
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
    public function trace(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->map(
            'TRACE',
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
    public function connect(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->map(
            'CONNECT',
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
    public function any(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->map(
            RouteInterface::ANY_METHODS,
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
    public function all(
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ): RouteInterface {
        return $this->map(
            '*',
            $pattern,
            $callback,
            $name,
            $priority,
            $host
        );
    }
}
