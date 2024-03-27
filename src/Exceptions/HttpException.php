<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Exceptions;

use Pentagonal\Sso\Core\Exceptions\Interfaces\HttpExceptionInterface;
use Pentagonal\Sso\Core\Utils\Http\Code;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class HttpException extends RuntimeException implements HttpExceptionInterface
{
    /**
     * @var ServerRequestInterface the request
     */
    protected ServerRequestInterface $request;

    /**
     * @var int the HTTP status code
     */
    protected int $statusCode = Code::INTERNAL_SERVER_ERROR;

    /**
     * @var string the HTTP status message
     */
    protected string $title;

    public function __construct(
        ServerRequestInterface $request,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->title ??= Code::statusMessage($this->getStatusCode()) ?: 'An error occurred';
        $message = $message ?: (
            Code::statusMessage($this->getStatusCode())?: 'An error occurred'
        );
        $this->request = $request;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return ServerRequestInterface the request
     */
    public function getRequest() : ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string the HTTP status message
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}
