<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Formatters\Interfaces;

use Pentagonal\Sso\Core\Services\Interfaces\EventManagerInterface;
use Psr\Http\Message\ResponseInterface;
use const JSON_UNESCAPED_SLASHES;

interface JsonFormatterInterface
{
    public const CONTENT_TYPE = 'application/json';

    public const DEFAULT_OPTIONS = JSON_UNESCAPED_SLASHES;

    public const DEFAULT_DEPTH = 512;

    /**
     * Check if debug
     *
     * @return bool
     */
    public function isDebug(): bool;

    /**
     * Set debug
     *
     * @param bool $debug
     */
    public function setDebug(bool $debug): void;

    /**
     * Set Content Type
     * if content type does not contain /.*json, it ignores the set
     *
     * @param string $contentType
     */
    public function setContentType(string $contentType);

    /**
     * Get Content Type
     *
     * @return string
     */
    public function getContentType(): string;

    /**
     * Set JSON Encode Options
     *
     * @param int $options
     */
    public function setOptions(int $options): void;

    /**
     * Get JSON Encode Options
     *
     * @return int
     */
    public function getOptions(): int;

    /**
     * Set JSON Encode Depth
     *
     * @param int $depth
     */
    public function setDepth(int $depth): void;

    /**
     * Get JSON Encode Depth
     *
     * @return int
     */
    public function getDepth(): int;

    /**
     * Encode data to JSON
     *
     * @param $data
     * @return string
     */
    public function encode($data): string;

    /**
     * Decode JSON data
     *
     * @param string $data
     * @param bool|null $assoc
     * @param int $depth
     * @param int $options
     */
    public function decode(
        string $data,
        ?bool $assoc = null,
        int $depth = 512,
        int $options = 0
    );

    /**
     * Get Last Error
     *
     * @return string
     */
    public function getLastError(): string;

    /**
     * Get Last Error Code
     *
     * @return int
     */
    public function getLastErrorCode(): int;

    /**
     * Set Event Manager
     *
     * @param EventManagerInterface|null $eventManager
     */
    public function setEventManager(?EventManagerInterface $eventManager): void;

    /**
     * Get Event Manager
     *
     * @return EventManagerInterface|null
     */
    public function getEventManager(): ?EventManagerInterface;

    /**
     * Format Success Response data to encoded JSON
     *
     * @param $data
     * @return string
     */
    public function encodeSuccess($data): string;

    /**
     * Format Error Response data to encoded JSON
     *
     * @param $data
     * @return string
     */
    public function encodeError($data): string;

    /**
     * Response JSON, set content type to application/json
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function responseJson(ResponseInterface $response): ResponseInterface;

    /**
     * Send success response
     *
     * @param ResponseInterface $response
     * @param $data
     * @return ResponseInterface
     */
    public function success(ResponseInterface $response, $data): ResponseInterface;

    /**
     * Send error response
     *
     * @param ResponseInterface $response
     * @param $data
     * @param array $metadata
     * @param int|null $code
     * @return ResponseInterface
     */
    public function error(
        ResponseInterface $response,
        $data,
        array $metadata = [],
        int $code = null
    ): ResponseInterface;
}
