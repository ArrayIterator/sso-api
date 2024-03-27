<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Exceptions;

use Pentagonal\Sso\Core\Exceptions\Interfaces\HttpExceptionInterface;
use Pentagonal\Sso\Core\Utils\Http\Code;

class HttpNotFoundException extends HttpException implements HttpExceptionInterface
{
    protected int $statusCode = Code::NOT_FOUND;
}
