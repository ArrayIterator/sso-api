<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Services;

use Closure;
use Pentagonal\Sso\Core\Services\Interfaces\MiddlewareServiceDispatcherInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareServiceDispatcher implements MiddlewareServiceDispatcherInterface
{
    private RequestHandlerInterface $stack;

    private ContainerInterface $container;

    public function __construct(
        RequestHandlerInterface $handler,
        ?ContainerInterface $container = null
    ) {
        $this->setStack($handler);
        $this->container = $container;
    }

    public function setStack(RequestHandlerInterface $handler): static
    {
        $this->stack = $handler;
        return $this;
    }

    public function add(callable|MiddlewareInterface $middleware): static
    {
        if ($middleware instanceof Closure) {
            $middleware = $middleware->bindTo($this->container);
        }
        $next = $this->stack;
        if ($middleware instanceof MiddlewareInterface) {
            $this->stack = new class($middleware, $next) implements RequestHandlerInterface {
                private MiddlewareInterface $middleware;
                private RequestHandlerInterface $next;
                public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $next)
                {
                    $this->middleware = $middleware;
                    $this->next = $next;
                }
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->middleware->process($request, $this->next);
                }
            };

            return $this;
        }

        $this->stack = new class($middleware, $next) implements RequestHandlerInterface {
            private $middleware;
            private RequestHandlerInterface $next;
            public function __construct(callable $middleware, RequestHandlerInterface $next)
            {
                $this->middleware = $middleware;
                $this->next = $next;
            }
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->middleware)($request, $this->next);
            }
        };

        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->stack->handle($request);
    }
}
