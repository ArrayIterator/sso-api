<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Exceptions;

use Pentagonal\Sso\Core\Database\Exceptions\Interfaces\DatabaseExceptionInterface;
use Pentagonal\Sso\Core\Exceptions\Exception as ExceptionCore;

class Exception extends ExceptionCore implements DatabaseExceptionInterface
{
}
