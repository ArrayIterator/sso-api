<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes\Exceptions;

use Pentagonal\Sso\Exceptions\MethodNotAllowedException;
use Pentagonal\Sso\Routes\Route;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use function sprintf;

class RouteMethodNotAllowedException extends MethodNotAllowedException
{
    protected ServerRequestInterface $request;

    protected Route $route;

    public function __construct(
        ServerRequestInterface $request,
        Route $route,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $allowedMethods = $route->getMethods();
        $this->request = $request;
        $this->route = $route;
        $message = sprintf(
            'Method "%s" is not allowed for route "%s". %s',
            $request->getMethod(),
            $route->getPattern(),
            sprintf(
                'Allowed methods: %s',
                implode(', ', $allowedMethods)
            )
        );
        parent::__construct($message, $code, $allowedMethods, $previous);
    }

    public function getRoute() : Route
    {
        return $this->route;
    }

    public function getRequest() : ServerRequestInterface
    {
        return $this->request;
    }
}
