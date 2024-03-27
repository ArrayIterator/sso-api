<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Handlers;

use GuzzleHttp\Psr7\HttpFactory;
use Pentagonal\Sso\Core\Exceptions\HttpMethodNotAllowedException;
use Pentagonal\Sso\Core\Exceptions\HttpNotFoundException;
use Pentagonal\Sso\Core\Exceptions\Interfaces\HttpExceptionInterface;
use Pentagonal\Sso\Core\Handlers\Interfaces\ErrorHandlerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;
use function strtolower;

class ExceptionHandler implements Interfaces\ExceptionHandlerInterface
{
    /**
     * @var string
     */
    protected string $type = self::DEFAULT_TYPE;

    /**
     * @var ServerRequestInterface
     */
    protected ServerRequestInterface $request;

    /**
     * @var array<ErrorHandlerInterface>
     */
    protected array $handlers = [];

    /**
     * @var ?ContainerInterface
     */
    protected ?ContainerInterface $container = null;

    /**
     * @var ?ResponseFactoryInterface
     */
    protected ?ResponseFactoryInterface $responseFactory = null;

    /**
     * @var ?StreamFactoryInterface
     */
    protected ?StreamFactoryInterface $streamFactory = null;

    /**
     * @var ErrorHandlerInterface
     */
    protected ErrorHandlerInterface $defaultHandler;

    /**
     * @var bool
     */
    protected bool $debug = false;

    public function __construct()
    {
        $this->defaultHandler = new HtmlHandler();
        $this->setDefaultHandler($this->defaultHandler);
        $this->setHandler(self::TYPE_HTML, $this->defaultHandler);
    }

    /**
     * @inheritDoc
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * @inheritDoc
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @inheritDoc
     */
    public function setDefaultHandler(ErrorHandlerInterface $handler): void
    {
        $this->defaultHandler = $handler;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultHandler(): ErrorHandlerInterface
    {
        return $this->defaultHandler;
    }

    /**
     * @inheritDoc
     */
    public function setContainer(?ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function setResponseFactory(ResponseFactoryInterface $responseFactory) : void
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @inheritDoc
     */
    public function setStreamFactory(StreamFactoryInterface $streamFactory) : void
    {
        $this->streamFactory = $streamFactory;
    }

    /**
     * @inheritDoc
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        if ($this->responseFactory) {
            return $this->responseFactory;
        }
        $container = $this->getContainer();
        if ($container?->has(ResponseFactoryInterface::class)) {
            try {
                $responseFactory = $container->get(ResponseFactoryInterface::class);
            } catch (Throwable) {
                // pass
            }
        }
        $responseFactory ??= new HttpFactory();
        if (!$responseFactory instanceof ResponseFactoryInterface) {
            $responseFactory = new HttpFactory();
        }
        $this->setResponseFactory($responseFactory);
        return $responseFactory;
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        if ($this->streamFactory) {
            return $this->streamFactory;
        }
        $container = $this->getContainer();
        if ($container?->has(StreamFactoryInterface::class)) {
            try {
                $streamFactory = $container->get(StreamFactoryInterface::class);
            } catch (Throwable) {
                // pass
            }
        }

        $streamFactory ??= new HttpFactory();
        if (!$streamFactory instanceof StreamFactoryInterface) {
            $streamFactory = new HttpFactory();
        }
        $this->setStreamFactory($streamFactory);
        return $streamFactory;
    }

    /**
     * @inheritDoc
     */
    public function setHandler(string $type, ErrorHandlerInterface $handler): void
    {
        $type = strtolower($type);
        $this->handlers[$type] = $handler;
    }

    /**
     * @inheritDoc
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function getHandler(string $type): ErrorHandlerInterface
    {
        $type = strtolower($type);
        $handler = $this->handlers[$type] ?? null;
        if (!$handler) {
            return $this->getDefaultHandler();
        }

        return $handler;
    }

    /**
     * @inheritDoc
     */
    public function handle(
        ServerRequestInterface $request,
        Throwable $exception
    ): ResponseInterface {
        $handler = $this->getHandler($this->getType());
        try {
            try {
                if ($exception instanceof HttpNotFoundException) {
                    return $handler->notFound($this, $exception);
                }
                if ($exception instanceof HttpMethodNotAllowedException) {
                    return $handler->methodNotAllowed($this, $exception);
                }
                if ($exception instanceof HttpExceptionInterface) {
                    return $handler->error($this, $exception);
                }
            } catch (Throwable $e) {
                $previous = $exception;
                $exception = $e;
            }

            return $handler->exception($this, $exception, $previous ?? null);
        } catch (Throwable $e) {
            return $this->getDefaultHandler()->exception($this, $e, $exception);
        }
    }
}
