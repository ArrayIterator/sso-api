<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Formatters;

use GuzzleHttp\Psr7\HttpFactory;
use Pentagonal\Sso\Core\Exceptions\Interfaces\HttpExceptionInterface;
use Pentagonal\Sso\Core\Exceptions\RuntimeException;
use Pentagonal\Sso\Core\Formatters\Interfaces\JsonFormatterInterface;
use Pentagonal\Sso\Core\Services\Interfaces\EventManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;
use function is_iterable;
use function iterator_to_array;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;
use function preg_match;
use function sprintf;
use function trim;
use const JSON_ERROR_NONE;

class Json implements JsonFormatterInterface
{
    /**
     * @var ?EventManagerInterface Event Manager
     */
    protected ?EventManagerInterface $eventManager;

    /**
     * @var int JSON Encode Options
     */
    protected int $options = self::DEFAULT_OPTIONS;

    /**
     * @var string Content Type
     */
    private string $contentType = self::CONTENT_TYPE;

    /**
     * @var int JSON Encode Depth
     */
    private int $depth = self::DEFAULT_DEPTH;

    /**
     * @var bool Debug
     */
    private bool $debug = false;

    /**
     * @var int Last Error Code
     */
    private int $lastErrorCode = JSON_ERROR_NONE;

    /**
     * @var string Last Error
     */
    private string $lastError = '';

    /**
     * @var ?StreamFactoryInterface
     */
    protected ?StreamFactoryInterface $streamFactory = null;

    private ?ContainerInterface $container = null;

    public function __construct(
        ?ContainerInterface $container = null,
        ?EventManagerInterface $eventManager = null
    ) {
        $this->setContainer($container);
        if (!$eventManager && $container?->has(EventManagerInterface::class)) {
            try {
                $eventManager = $container->get(EventManagerInterface::class);
            } catch (Throwable) {
                // pass
            }
        }
        $eventManager = $eventManager instanceof EventManagerInterface ? $eventManager : null;
        $this->setEventManager($eventManager);
    }

    /**
     * Set Container
     *
     * @param ContainerInterface|null $container
     * @return void
     */
    public function setContainer(?ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Get Container
     *
     * @return ?ContainerInterface
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * Trigger Event
     *
     * @param string $name
     * @param ...$arguments
     * @return void
     */
    protected function triggerEvent(string $name, ...$arguments): void
    {
        $this->getEventManager()?->trigger($name, $this, ...$arguments);
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
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * @inheritDoc
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * @inheritDoc
     */
    public function getLastErrorCode(): int
    {
        return $this->lastErrorCode;
    }

    /**
     * @inheritDoc
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @inheritDoc
     * @return bool true if success
     */
    public function setContentType(string $contentType): bool
    {
        $contentType = trim($contentType);
        if (!preg_match('~^[^/]+/.*json~i', $contentType)) {
            return false;
        }
        $this->contentType = $contentType;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getOptions(): int
    {
        return $this->options;
    }

    /**
     * @inheritDoc
     */
    public function setOptions(int $options): void
    {
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @inheritDoc
     */
    public function setDepth(int $depth): void
    {
        $this->depth = $depth > 0
            ? $depth
            : self::DEFAULT_DEPTH;
    }

    /**
     * @inheritDoc
     */
    public function getEventManager(): ?EventManagerInterface
    {
        return $this->eventManager;
    }

    /**
     * @inheritDoc
     */
    public function setEventManager(?EventManagerInterface $eventManager): void
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @inheritDoc
     */
    public function encode(mixed $data) : string
    {
        $this->triggerEvent('json.encode.start', $data);
        $result = json_encode($data, $this->getOptions(), $this->getDepth());
        $this->lastErrorCode = json_last_error();
        $this->lastError = json_last_error_msg();
        if ($result === false) {
            $this->triggerEvent('json.error', $data, $this->lastErrorCode, $this->lastError);
            throw new RuntimeException(
                sprintf(
                    'Error while encoding data to JSON. Error: "%s", Message: %s',
                    $this->lastErrorCode,
                    $this->lastError
                )
            );
        }

        $this->triggerEvent('json.encode.end', $result, $data);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function decode(string $data, ?bool $assoc = null, int $depth = 512, int $options = 0)
    {
        $assoc ??= true;
        $this->triggerEvent('json.decode.start', $data);
        $result = json_decode($data, $assoc, $depth, $options);
        $this->lastErrorCode = json_last_error();
        $this->lastError = json_last_error_msg();
        if ($result === null && $this->lastErrorCode !== JSON_ERROR_NONE) {
            $this->triggerEvent('json.error', $data, $this->lastErrorCode, $this->lastError);
            throw new RuntimeException(
                sprintf(
                    'Error while decoding data from JSON. Error: "%s", Message: %s',
                    $this->lastErrorCode,
                    $this->lastError
                )
            );
        }
        $this->triggerEvent('json.decode.end', $result, $data);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function encodeSuccess(mixed $data): string
    {
        return $this->encode(['data' => $data]);
    }

    /**
     * @inheritDoc
     */
    public function encodeError($data, array $metadata = [], int $code = null): string
    {
        $messageKey = is_iterable($data) ? 'messages' : 'message';
        if (!$code) {
            $code = $data instanceof HttpExceptionInterface
                ? $data->getStatusCode()
                : 500;
        }

        if ($data instanceof Throwable) {
            if (empty($metadata) && $this->isDebug()) {
                $metadata = [
                    'file' => $data->getFile(),
                    'line' => $data->getLine(),
                    'trace' => $data->getTrace(),
                ];
            }
            $data = $data->getMessage();
        } else {
            $data = iterator_to_array($data);
        }

        return $this->encode([
            'code' => $code,
            $messageKey => $data,
            'metadata' => $metadata,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function responseJson(ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader('Content-Type', $this->getContentType());
    }

    /**
     * @return StreamFactoryInterface
     */
    protected function getStreamFactoryObject() : StreamFactoryInterface
    {
        if ($this->streamFactory) {
            return $this->streamFactory;
        }
        $container = $this->getContainer();
        $streamFactory = null;
        if ($container?->has(StreamFactoryInterface::class)) {
            try {
                $streamFactory = $container->get(StreamFactoryInterface::class);
            } catch (Throwable) {
                // pass
            }
        }
        $streamFactory ??= new HttpFactory();
        return $this->streamFactory = $streamFactory;
    }

    /**
     * @inheritDoc
     */
    public function success(ResponseInterface $response, mixed $data): ResponseInterface
    {
        $stream = $response->getBody();
        if ($stream->getSize() > 0 || !$stream->isWritable()) {
            $stream = $this->getStreamFactoryObject()->createStream();
        }
        $stream->write($this->encodeSuccess($data));
        return $this->responseJson($response)->withBody($stream);
    }

    /**
     * @inheritDoc
     */
    public function error(
        ResponseInterface $response,
        $data,
        array $metadata = [],
        int $code = null
    ): ResponseInterface {
        if (!$code) {
            $code = $data instanceof HttpExceptionInterface
                ? $data->getStatusCode()
                : 500;
        }
        $stream = $response->getBody();
        if ($stream->getSize() > 0 || !$stream->isWritable()) {
            $stream = $this->getStreamFactoryObject()->createStream();
        }
        $stream->write($this->encodeError($data, $metadata, $code));
        return $this->responseJson($response)->withBody($stream)->withStatus($code);
    }
}
