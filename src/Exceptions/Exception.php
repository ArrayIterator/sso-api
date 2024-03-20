<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Exceptions;

use Exception as CoreException;
use Pentagonal\Sso\Core\Exceptions\Interfaces\SystemExceptionInterface;

class Exception extends CoreException implements SystemExceptionInterface
{
}
