<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes;

use Pentagonal\Sso\Routes\Interfaces\ControllerInterface;
use Pentagonal\Sso\Routes\Interfaces\RouteInterface;
use Pentagonal\Sso\Routes\Interfaces\RouteMethodInterface;
use Pentagonal\Sso\Routes\Interfaces\RouterInterface;
use Pentagonal\Sso\Routes\Traits\RouteMethodTrait;
use function call_user_func;

class RouteGroup implements Interfaces\RouteGroupInterface, RouteMethodInterface
{
    use RouteMethodTrait;

    protected $callable;

    private bool $processed = false;

    public function __construct(
        protected RouterInterface $router,
        protected string $pattern,
        callable $callback,
        protected ?Interfaces\RouteGroupInterface $previousGroup = null
    ) {
        $this->callable = $callback;
    }

    /**
     * @inheritDoc
     */
    public function getPattern() : string
    {
        return $this->pattern;
    }

    /**
     * Get previous group
     *
     * @return Interfaces\RouteGroupInterface|null
     */
    public function getPreviousGroup() : ?Interfaces\RouteGroupInterface
    {
        return $this->previousGroup;
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
            $this->getPattern() . $pattern,
            $callback,
            $name,
            $priority,
            $host
        );
    }

    /**
     * @inheritDoc
     */
    public function group(
        string $pattern,
        callable $callback
    ): RouterInterface {
        return $this->router->group(
            $this->getPattern() . $pattern,
            $callback
        );
    }

    /**
     * @inheritDoc
     */
    public function dispatch(): void
    {
        if ($this->processed) {
            return;
        }
        // set processed to avoid multiple process
        $this->processed = true;
        $manager = $this->router->getRoutes()->getEventManager();
        $manager?->trigger('route.group.start', $this);
        call_user_func($this->callable, $this);
        $manager?->trigger('route.group.end', $this);
    }
}
