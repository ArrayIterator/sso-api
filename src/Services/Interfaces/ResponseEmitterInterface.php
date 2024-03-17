<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Services\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

interface ResponseEmitterInterface
{
    /**
     * @return ?StreamInterface
     */
    public function getPreviousOutput(): ?StreamInterface;

    /**
     * Emit the response
     *
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response);

    /**
     * Close the connection
     */
    public function close();
}
