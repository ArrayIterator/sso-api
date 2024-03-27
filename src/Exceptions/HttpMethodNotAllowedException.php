<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Exceptions;

use Pentagonal\Sso\Core\Utils\Http\Code;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use function array_filter;
use function sprintf;

class HttpMethodNotAllowedException extends HttpException
{
    /**
     * @var array<string>
     */
    protected array $allowedMethods = [];

    /**
     * @var int
     */
    protected int $statusCode = Code::METHOD_NOT_ALLOWED;

    public function __construct(
        ServerRequestInterface $request,
        string $message = "",
        int $code = 0,
        array $allowedMethods = [],
        ?Throwable $previous = null
    ) {
        $this->allowedMethods = array_filter($allowedMethods, 'is_string');
        if (empty($message)) {
            $message = sprintf(
                'Method "%s" is not allowed. Allowed methods: %s',
                $request->getMethod(),
                implode(', ', $this->allowedMethods)
            );
        }
        parent::__construct($request, $message, $code, $previous);
    }

    /**
     * @return array<string>
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
