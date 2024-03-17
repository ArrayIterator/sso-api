<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Exceptions;

use Pentagonal\Sso\Exceptions\Interfaces\RouteExceptionInterface;
use Throwable;

class RouteNotFoundException extends NotFoundException implements RouteExceptionInterface
{
    public function __construct(string $message = 'Route Not Found', int $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
