<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Exceptions;

use Pentagonal\Sso\Core\Exceptions\HttpException;
use Pentagonal\Sso\Core\Routes\Route;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class RouteErrorException extends HttpException
{
    protected int $statusCode = 500;

    /**
     * @var Route
     */
    protected Route $route;

    public function __construct(
        ServerRequestInterface $request,
        Route $route,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);
    }

    /**
     * @return Route the route
     */
    public function getRoute() : Route
    {
        return $this->route;
    }
}
