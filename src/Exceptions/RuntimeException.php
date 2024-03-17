<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Exceptions;

use RuntimeException as CoreException;
use Pentagonal\Sso\Exceptions\Interfaces\SystemExceptionInterface;

class RuntimeException extends CoreException implements SystemExceptionInterface
{
}
