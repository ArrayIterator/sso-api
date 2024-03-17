<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Exceptions;

use InvalidArgumentException as CoreException;
use Pentagonal\Sso\Exceptions\Interfaces\SystemExceptionInterface;

class InvalidArgumentException extends CoreException implements SystemExceptionInterface
{
}
