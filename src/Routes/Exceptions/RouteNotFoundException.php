<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes\Exceptions;

use Pentagonal\Sso\Exceptions\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class RouteNotFoundException extends NotFoundException
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
        parent::__construct($message, $code, $previous);
    }

    public function getRequest() : ServerRequestInterface
    {
        return $this->request;
    }
}
