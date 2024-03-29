<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Exceptions;

use Pentagonal\Sso\Core\Exceptions\HttpMethodNotAllowedException;
use Pentagonal\Sso\Core\Routes\Route;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use function sprintf;

class RouteHttpMethodNotAllowedException extends HttpMethodNotAllowedException
{
    /**
     * @var ServerRequestInterface
     */
    protected ServerRequestInterface $request;

    /**
     * @var Route
     */
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
        parent::__construct($request, $message, $code, $allowedMethods, $previous);
    }

    public function getRoute() : Route
    {
        return $this->route;
    }
}
