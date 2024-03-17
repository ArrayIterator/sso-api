<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Exceptions;

use Pentagonal\Sso\Exceptions\Interfaces\SystemExceptionInterface;

class MaxCallStackExceededException extends RuntimeException implements SystemExceptionInterface
{
    final public const MAX_CALL_STACK_EXCEEDED = 256;
}
