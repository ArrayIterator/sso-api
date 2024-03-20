<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Exceptions;

use Pentagonal\Sso\Core\Exceptions\Interfaces\HttpExceptionInterface;
use Throwable;

class NotFoundException extends Exception implements HttpExceptionInterface
{
    public function __construct(
        string $message = "Not Found",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): int
    {
        return 404;
    }
}
