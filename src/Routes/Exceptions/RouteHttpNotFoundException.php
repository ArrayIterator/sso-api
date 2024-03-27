<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Exceptions;

use Pentagonal\Sso\Core\Exceptions\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class RouteHttpNotFoundException extends HttpNotFoundException
{
    protected ServerRequestInterface $request;

    public function __construct(
        ServerRequestInterface $request,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message ??= 'Route not found';
        $this->request = $request;
        parent::__construct($request, $message, $code, $previous);
    }

    public function getRequest() : ServerRequestInterface
    {
        return $this->request;
    }
}
