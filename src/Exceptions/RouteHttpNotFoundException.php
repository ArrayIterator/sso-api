<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Exceptions;

use Pentagonal\Sso\Core\Exceptions\Interfaces\RouteExceptionInterface;
use Throwable;

class RouteHttpNotFoundException extends HttpNotFoundException implements RouteExceptionInterface
{
    public function __construct(string $message = 'Route Not Found', int $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
