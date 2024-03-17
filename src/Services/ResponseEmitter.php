<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Services;

use GuzzleHttp\Psr7\Stream;
use Pentagonal\Sso\Services\Interfaces\EventManagerInterface;
use Pentagonal\Sso\Services\Interfaces\ResponseEmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use function fastcgi_finish_request;
use function fopen;
use function function_exists;
use function header;
use function headers_sent;
use function ob_end_clean;
use function ob_get_level;
use function sprintf;

/**
 * Response Emitter, emit the response
 */
class ResponseEmitter implements ResponseEmitterInterface
{
    /**
     * @var ?EventManagerInterface $eventManager Event Manager
     */
    protected ?EventManagerInterface $eventManager;

    /**
     * @var ?StreamInterface $previousOutput Previous output
     */
    protected ?StreamInterface $previousOutput = null;

    /**
     * ResponseEmitter constructor.
     *
     * @param EventManagerInterface|null $eventManager
     */
    public function __construct(?EventManagerInterface $eventManager = null)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Set the event manager
     *
     * @param EventManagerInterface|null $eventManager
     */
    public function setEventManager(?EventManagerInterface $eventManager): void
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Get the event manager
     *
     * @return EventManagerInterface|null
     */
    public function getEventManager(): ?EventManagerInterface
    {
        return $this->eventManager;
    }

    /**
     * @inheritDoc
     */
    public function getPreviousOutput(): ?StreamInterface
    {
        return $this->previousOutput;
    }

    /**
     * Emit the response
     *
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response): void
    {
        // If there is an output buffer, save it
        if (ob_get_level() > 0) {
            $this->previousOutput = new Stream(fopen('php://output', 'r+'));
            if (ob_get_length() > 0) {
                $this->previousOutput->write(ob_get_contents());
                $this->clearOutputBuffer();
                ob_start(); // start new buffer
            }
        }

        // Emit the response
        $this->getEventManager()?->trigger(
            'response.emit',
            $response,
            $this
        );

        if (!headers_sent()) {
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        echo $response->getBody();
        $this->getEventManager()?->trigger(
            'response.emitted',
            $response,
            $this
        );
    }

    /**
     * Clear the output buffer
     */
    public function clearOutputBuffer(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
}
