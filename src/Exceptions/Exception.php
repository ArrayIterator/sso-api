<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Exceptions;

use Exception as CoreException;
use Pentagonal\Sso\Exceptions\Interfaces\SystemExceptionInterface;

class Exception extends CoreException implements SystemExceptionInterface
{
}
