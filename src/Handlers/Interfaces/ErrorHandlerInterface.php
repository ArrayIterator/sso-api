<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Handlers\Interfaces;

use Pentagonal\Sso\Core\Exceptions\HttpMethodNotAllowedException;
use Pentagonal\Sso\Core\Exceptions\HttpNotFoundException;
use Pentagonal\Sso\Core\Exceptions\Interfaces\HttpExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

interface ErrorHandlerInterface
{
    /**
     * Handle not found
     *
     * @param ExceptionHandlerInterface $handler
     * @param HttpNotFoundException $exception
     * @return ResponseInterface
     */
    public function notFound(
        ExceptionHandlerInterface $handler,
        HttpNotFoundException $exception
    ): ResponseInterface;

    /**
     * Handle method not allowed
     *
     * @param ExceptionHandlerInterface $handler
     * @param HttpMethodNotAllowedException $exception
     * @return ResponseInterface
     */
    public function methodNotAllowed(
        ExceptionHandlerInterface $handler,
        HttpMethodNotAllowedException $exception
    ): ResponseInterface;

    /**
     * Handle http error
     *
     * @param ExceptionHandlerInterface $handler
     * @param HttpExceptionInterface $exception
     * @return ResponseInterface
     */
    public function error(
        ExceptionHandlerInterface $handler,
        HttpExceptionInterface $exception
    ): ResponseInterface;

    /**
     * Handle exception
     *
     * @param ExceptionHandlerInterface $handler
     * @param Throwable $exception
     * @param Throwable|null $previous
     * @return ResponseInterface
     */
    public function exception(
        ExceptionHandlerInterface $handler,
        Throwable $exception,
        ?Throwable $previous = null
    ): ResponseInterface;
}
