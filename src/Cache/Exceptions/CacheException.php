<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Cache\Exceptions;

use Pentagonal\Sso\Core\Cache\Interfaces\CacheExceptionInterface;
use Pentagonal\Sso\Core\Exceptions\Exception;

class CacheException extends Exception implements CacheExceptionInterface
{
}
