<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes;

use Pentagonal\Sso\Core\Routes\Interfaces\RouteDispatcherInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteResultInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class RouteResult implements RouteResultInterface
{
    private ServerRequestInterface $request;

    private ?RouteInterface $route;

    public function __construct(
        ServerRequestInterface $request,
        private RouteDispatcherInterface $dispatcher,
        private int $routeStatus,
        private array $matchesParams = [],
        ?RouteInterface $route = null
    ) {
        $this->request = $request->withAttribute(RouteContext::ROUTE_RESULT, $this);
        if (!$route) {
            $route = $this->request->getAttribute(RouteContext::ROUTE);
            if (!$route instanceof RouteInterface) {
                $route = null;
            }
        }
        $this->route = $route;
    }

    /**
     * @inheritDoc
     */
    public function getDispatcher(): RouteDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @inheritDoc
     */
    public function getRouteStatus(): int
    {
        return $this->routeStatus;
    }

    /**
     * @inheritDoc
     */
    public function getMatchedParams(): array
    {
        return $this->matchesParams;
    }

    /**
     * @inheritDoc
     */
    public function getRoute(): ?RouteInterface
    {
        return $this->route;
    }

    /**
     * @inheritDoc
     */
    public function isFound(): bool
    {
        return $this->routeStatus === RouteResultInterface::FOUND;
    }
}
