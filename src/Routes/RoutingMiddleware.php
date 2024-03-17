<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes;

use Pentagonal\Sso\Routes\Interfaces\RoutesInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class RoutingMiddleware implements MiddlewareInterface
{

    public function __construct(private RoutesInterface $routes)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->routes->performRouting($request);
        return $handler->handle($request);
    }
}
