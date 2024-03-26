<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Cache\Exceptions;

use Pentagonal\Sso\Core\Cache\Interfaces\CacheExceptionInterface;
use Pentagonal\Sso\Core\Exceptions\InvalidArgumentException as InvalidArgumentExceptionCore;

class InvalidArgumentException extends InvalidArgumentExceptionCore implements CacheExceptionInterface
{
}
