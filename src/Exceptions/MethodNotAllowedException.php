<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Exceptions;

use Pentagonal\Sso\Exceptions\Interfaces\HttpExceptionInterface;
use Throwable;

class MethodNotAllowedException extends RuntimeException implements HttpExceptionInterface
{
    protected array $allowedMethods = [];

    public function __construct(
        string $message = "Method Not Allowed",
        int $code = 0,
        array $allowedMethods = [],
        ?Throwable $previous = null
    ) {
        $this->allowedMethods = $allowedMethods;
        parent::__construct($message, $code, $previous);
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

    public function getStatusCode(): int
    {
        return 405;
    }
}
