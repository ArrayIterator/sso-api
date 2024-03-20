<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Exceptions;

use RuntimeException as CoreException;
use Pentagonal\Sso\Core\Exceptions\Interfaces\SystemExceptionInterface;

class RuntimeException extends CoreException implements SystemExceptionInterface
{
}
