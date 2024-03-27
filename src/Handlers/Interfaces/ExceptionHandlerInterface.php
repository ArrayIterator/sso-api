<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Handlers\Interfaces;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

interface ExceptionHandlerInterface
{
    public const TYPE_HTML = 'html';

    public const TYPE_JSON = 'json';

    public const TYPE_XML  = 'xml';

    public const TYPE_TEXT = 'text';

    public const DEFAULT_TYPE = self::TYPE_HTML;

    /**
     * Set debug
     *
     * @param bool $debug
     */
    public function setDebug(bool $debug);

    /**
     * @return bool is debug
     */
    public function isDebug() : bool;

    /**
     * Set default handler
     *
     * @param ErrorHandlerInterface $handler
     */
    public function setDefaultHandler(ErrorHandlerInterface $handler);

    /**
     * Get default handler
     * @return ErrorHandlerInterface
     */
    public function getDefaultHandler(): ErrorHandlerInterface;

    /**
     * Set error handler
     *
     * @param string $type
     * @param ErrorHandlerInterface $handler
     */
    public function setHandler(string $type, ErrorHandlerInterface $handler);

    /**
     * Set content type
     *
     * @param string $type
     */
    public function setType(string $type);

    /**
     * Get type
     *
     * @return string
     */
    public function getType() : string;

    /**
     * Get error handler object, if not found will use default
     *
     * @param string $type
     * @return ErrorHandlerInterface
     */
    public function getHandler(string $type) : ErrorHandlerInterface;

    /**
     * Set container
     *
     * @param ?ContainerInterface $container
     */
    public function setContainer(?ContainerInterface $container);

    /**
     * Get container
     *
     * @return ?ContainerInterface
     */
    public function getContainer() : ?ContainerInterface;

    /**
     * Set response factory
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public function setResponseFactory(ResponseFactoryInterface $responseFactory);

    /**
     * @param StreamFactoryInterface $streamFactory
     */
    public function setStreamFactory(StreamFactoryInterface $streamFactory);

    /**
     * Get Response Factory
     *
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory() : ResponseFactoryInterface;

    /**
     * Get Stream Factory
     * @return StreamFactoryInterface
     */
    public function getStreamFactory() : StreamFactoryInterface;

    /**
     * Handle the error
     *
     * @param ServerRequestInterface $request
     * @param Throwable $exception
     * @return ResponseInterface
     */
    public function handle(
        ServerRequestInterface $request,
        Throwable $exception
    ) : ResponseInterface;
}
