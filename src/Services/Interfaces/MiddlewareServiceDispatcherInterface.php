<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Services\Interfaces;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface MiddlewareServiceDispatcherInterface extends RequestHandlerInterface
{
    /**
     * @param callable|MiddlewareInterface $middleware
     */
    public function add(callable|MiddlewareInterface $middleware) : static;

    public function setStack(RequestHandlerInterface $handler): static;
}
