<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Exceptions;

use InvalidArgumentException as CoreException;
use Pentagonal\Sso\Core\Exceptions\Interfaces\SystemExceptionInterface;

class InvalidArgumentException extends CoreException implements SystemExceptionInterface
{
}
